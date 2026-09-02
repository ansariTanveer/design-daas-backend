<?php

declare(strict_types=1);

use Application\Common\Application\Cli\CliDispatcherInterface;
use Application\Common\Application\Cli\SymfonyConsoleCliDispatcher;
use Application\Common\SymfonyConsole\CommandCache;
use Application\Common\SymfonyConsole\CommandCacheInterface;
use Application\Common\SymfonyConsole\CommandCollector;
use Application\Common\SymfonyConsole\CommandCollectorInterface;
use Application\Core\Util\Main\VersionDetails;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

return [
    'application.dispatcher.command_collector' => \DI\factory(
        function (ContainerInterface $container): CommandCollectorInterface {
            $collector = new CommandCollector();
            $collector->addSearchPath(
                dirname(__DIR__, 3) . '/src',
                'Application\\Core',
                '*CliCommands.php',
            );
            return $collector;
        },
    ),
    'application.dispatcher.command_cache' => \DI\factory(
        function (ContainerInterface $container): CommandCacheInterface {
            $collector = $container->get('application.dispatcher.command_collector');
            assert($collector instanceof CommandCollectorInterface);
            $useCache = $container->get('application.runtime.cache_enabled');
            assert(is_bool($useCache));
            $cacheDir = $container->get('application.runtime.cache_dir');
            assert($cacheDir === null || is_string($cacheDir));
            return new CommandCache(
                $collector,
                $useCache ?
                    sprintf('%1$s/cli-commands.cache', $cacheDir) :
                    null,
            );
        },
    ),
    'application.dispatcher' => \DI\factory(
        function (ContainerInterface $container): CliDispatcherInterface {
            $versionDetails = $container->get(VersionDetails::class);
            assert($versionDetails instanceof VersionDetails);
            $commandCache = $container->get('application.dispatcher.command_cache');
            assert($commandCache instanceof CommandCacheInterface);
            $eventDispatcher = $container->get(EventDispatcherInterface::class);
            assert($eventDispatcher instanceof EventDispatcherInterface);
            return new SymfonyConsoleCliDispatcher(
                $container,
                $commandCache,
                $versionDetails->applicationName(),
                $versionDetails->applicationVersion(),
                $eventDispatcher,
            );
        },
    ),
];
