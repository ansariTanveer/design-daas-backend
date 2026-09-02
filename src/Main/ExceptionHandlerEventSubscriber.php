<?php

declare(strict_types=1);

namespace Application\Core\Main;

use Application\Common\Application\UnexpectedExceptionEvent;
use Application\Core\Util\Main\VersionDetails;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class ExceptionHandlerEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private VersionDetails $versionDetails,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            UnexpectedExceptionEvent::class => ['handleUnexpectedExceptionEvent'],
        ];
    }

    public function handleUnexpectedExceptionEvent(UnexpectedExceptionEvent $event): void
    {
        $this->logger->critical(
            'Unexpected exception',
            [
                'version_details' => $this->versionDetails,
                'exception' => $event->exception(),
            ],
        );
    }
}
