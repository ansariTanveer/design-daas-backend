<?php

namespace Application\Core\Util\OAuth2;

use Application\Core\OAuth2\Repository\OAuth2UserRepository;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Psr\Http\Message\ServerRequestInterface;

final class VerifyingRefreshTokenGrant extends RefreshTokenGrant
{
    public function __construct(
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        // @phpstan-ignore-next-line (Exception to testCoreNamespaceIsOnlyUsedFromTestNamespace rule)
        private readonly OAuth2UserRepository $oAuth2UserRepository
    ) {
        parent::__construct($refreshTokenRepository);
        $this->setUserRepository($oAuth2UserRepository);
    }

    /**
     * @return array<string, mixed>
     * @throws OAuthServerException
     */
    protected function validateOldRefreshToken(ServerRequestInterface $request, $clientId): array
    {
        $refreshTokenData = parent::validateOldRefreshToken($request, $clientId);

        if (!$this->oAuth2UserRepository->verifyUserIdentifier($refreshTokenData['user_id'])) {
            throw OAuthServerException::invalidRefreshToken('User is no longer valid');
        }

        return $refreshTokenData;
    }
}
