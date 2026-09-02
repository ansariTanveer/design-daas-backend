<?php

declare(strict_types=1);

namespace Application\Core\Main;

use Application\Common\Application\Cli\CliDispatcherFinishedEvent;
use Application\Common\Application\Http\HttpDispatcherFinishedEvent;
use DI\Annotation\Inject;
use Monolog\Logger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class PrimaryLoggerEventSubscriber implements EventSubscriberInterface
{
    /**
     * @Inject({
     *     "logger": "application.log.primary_logger",
     * })
     */
    public function __construct(private Logger $logger)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CliDispatcherFinishedEvent::class => ['handleCliDispatcherFinishedEvent', PHP_INT_MIN],
            HttpDispatcherFinishedEvent::class => ['handleHttpDispatcherFinishedEvent', PHP_INT_MIN],
        ];
    }

    public function handleCliDispatcherFinishedEvent(CliDispatcherFinishedEvent $event): void
    {
        $this->logger->debug(
            'CLI dispatcher finished, resetting logger',
            [
                'successful' => $event->successful(),
                'exit_code' => $event->exitCode(),
            ],
        );
        $this->logger->close();
        $this->logger->reset();
    }

    public function handleHttpDispatcherFinishedEvent(HttpDispatcherFinishedEvent $event): void
    {
        $this->logger->debug(
            'HTTP dispatcher finished, resetting logger',
            [
                'successful' => $event->successful(),
                'status_code' => $event->statusCode(),
            ],
        );
        $this->logger->close();
        $this->logger->reset();
    }
}
