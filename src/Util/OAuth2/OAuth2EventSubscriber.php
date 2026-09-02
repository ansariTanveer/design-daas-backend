<?php

namespace Application\Core\Util\OAuth2;

use Application\Core\Util\Main\CleanupEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

readonly class OAuth2EventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private OAuth2TokenService $tokenService,
    ) {
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            CleanupEvent::class => 'deleteExpiredTokens',
        ];
    }

    public function deleteExpiredTokens(): void
    {
        $this->tokenService->cleanUp();
    }
}
