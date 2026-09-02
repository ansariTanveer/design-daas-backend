<?php

declare(strict_types=1);

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\FilterHandler;
use Monolog\Handler\FingersCrossed\ErrorLevelActivationStrategy;
use Monolog\Handler\FingersCrossedHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NoopHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\WhatFailureGroupHandler;
use Monolog\Logger;
use Monolog\Processor\ProcessorInterface;
use Monolog\Processor\UidProcessor;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

return [
    'application.log.uid_processor' => \DI\factory(
        function (ContainerInterface $container): ProcessorInterface {
            return new UidProcessor(32);
        },
    ),
    'application.log.stdout_handler' => \DI\factory(
        function (ContainerInterface $container): HandlerInterface {
            $enabled = $container->get('config.log.stdout');
            if (!in_array($enabled, ['1', 'yes'], true)) {
                return new NoopHandler();
            }

            $stackTraceFormatter = new LineFormatter();
            $stackTraceFormatter->includeStacktraces(true);
            $handler = new StreamHandler('php://stdout');
            $handler->setFormatter($stackTraceFormatter);

            return $handler;
        },
    ),
    'application.log.file_handler' => \DI\factory(
        function (ContainerInterface $container): HandlerInterface {
            $file = $container->get('config.log.file');
            if ($file === null) {
                return new NoopHandler();
            }
            assert(is_string($file));

            $stackTraceFormatter = new LineFormatter();
            $stackTraceFormatter->includeStacktraces(true);
            $handler = new StreamHandler($file);
            $handler->setFormatter($stackTraceFormatter);

            return $handler;
        },
    ),
    'application.log.primary_handler_list' => \DI\factory(
        function (ContainerInterface $container): array {
            return [
                $container->get('application.log.stdout_handler'),
                $container->get('application.log.file_handler'),
            ];
        },
    ),
    'application.log.error_details_handler_list' => \DI\factory(
        function (ContainerInterface $container): array {
            return [
            ];
        },
    ),
    'application.log.primary_handler' => \DI\factory(
        function (ContainerInterface $container): HandlerInterface {
            /** @var HandlerInterface[] $handlerList */
            $handlerList = $container->get('application.log.primary_handler_list');

            /** @psalm-var LogLevel::* $logLevel */
            $logLevel = $container->get('config.log.level');
            $logLevel = Logger::toMonologLevel($logLevel);

            return new FilterHandler(
                new WhatFailureGroupHandler($handlerList),
                $logLevel,
            );
        },
    ),
    'application.log.error_details_handler' => \DI\factory(
        function (ContainerInterface $container): HandlerInterface {
            /** @var HandlerInterface[] $handlerList */
            $handlerList = $container->get('application.log.error_details_handler_list');

            /** @psalm-var LogLevel::* $logLevel */
            $logLevel = $container->get('config.log.level');
            $logLevel = Logger::toMonologLevel($logLevel);
            $triggerLevel = max($logLevel, Logger::toMonologLevel(LogLevel::ERROR));

            return new FingersCrossedHandler(
                new WhatFailureGroupHandler($handlerList),
                new ErrorLevelActivationStrategy($triggerLevel),
                0,
                true,
                true,
                $logLevel,
            );
        },
    ),
    'application.log.primary_logger' => \DI\factory(
        function (ContainerInterface $container): Logger {
            $logger = new Logger('Main');

            $uidProcessor = $container->get('application.log.uid_processor');
            assert($uidProcessor instanceof ProcessorInterface);
            $logger->pushProcessor($uidProcessor);

            $primaryHandler = $container->get('application.log.primary_handler');
            assert($primaryHandler instanceof HandlerInterface);
            $logger->pushHandler($primaryHandler);

            $errorDetailsHandler = $container->get('application.log.error_details_handler');
            assert($errorDetailsHandler instanceof HandlerInterface);
            $logger->pushHandler($errorDetailsHandler);

            return $logger;
        },
    ),
    LoggerInterface::class => \DI\get('application.log.primary_logger'),
];
