<?php

namespace Application\Core\Util\OAuth2;

use Application\Core\Util\OAuth2\Repository\AccessTokenRepository;
use Application\Core\Util\OAuth2\Repository\RefreshTokenRepository;

readonly final class OAuth2TokenService
{
    public function __construct(
        private AccessTokenRepository $accessTokenRepository,
        private RefreshTokenRepository $refreshTokenRepository
    ) {
    }

    public function cleanUp(): void
    {
        //Delete access tokens
        $this->accessTokenRepository->deleteExpiredTokens();

        //Delete refresh tokens
        $this->refreshTokenRepository->deleteExpiredTokens();
    }
}
