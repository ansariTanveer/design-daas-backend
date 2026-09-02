<?php

declare(strict_types=1);

namespace Application\Core\Main;

use Application\Common\Application\Http\InvalidUserInputEvent;
use Application\Common\HttpResponse\HttpResponseFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class InvalidUserInputEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private HttpResponseFactory $responseFactory,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            InvalidUserInputEvent::class => ['handleInvalidUserInputEvent', PHP_INT_MAX],
        ];
    }

    public function handleInvalidUserInputEvent(InvalidUserInputEvent $event): void
    {
        $this->logger->debug(
            'Invalid user input',
            [
                'exception_type' => get_class($event),
                'exception_message' => $event->exception()->getMessage(),
            ],
        );

        $event->replaceResponse(
            $this->responseFactory->buildJsonMessage(
                $event->response()->getStatusCode(),
                $event->exception()->getMessage(),
            ),
        );
    }
}
