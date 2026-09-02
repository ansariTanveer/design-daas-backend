<?php

namespace Application\Core\OAuth2;

use Application\Core\OAuth2\Repository\OAuth2UserRepository;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Service\PermissionCalculationService;
use DateInterval;
use Exception;
use League\OAuth2\Server\Entities\UserEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\RequestAccessTokenEvent;
use League\OAuth2\Server\RequestEvent;
use League\OAuth2\Server\RequestRefreshTokenEvent;
use League\OAuth2\Server\ResponseTypes\ResponseTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use stdClass;

class OAuth2PasswordGrant extends PasswordGrant
{
    public function __construct(
        UserRepositoryInterface $userRepository,
        RefreshTokenRepositoryInterface $refreshTokenRepository,
        private PermissionCalculationService $permissionCalculationService,
        private LoggerInterface $logger,
    ) {
        parent::__construct($userRepository, $refreshTokenRepository);
    }

    /**
     * @throws UniqueTokenIdentifierConstraintViolationException
     * @throws OAuthServerException
     */
    public function respondToAccessTokenRequest(
        ServerRequestInterface $request,
        ResponseTypeInterface $responseType,
        DateInterval $accessTokenTTL,
    ): ResponseTypeInterface {
        // Validate request
        $client = $this->validateClient($request);
        $scopes = $this->validateScopes($this->getRequestParameter('scope', $request, $this->defaultScope));
        $user = $this->validateUser($request, $client);

        // Finalize the requested scopes
        $finalizedScopes = $this->scopeRepository->finalizeScopes(
            $scopes,
            $this->getIdentifier(),
            $client,
            $user->getIdentifier()  /** @phpstan-ignore-line */
        );

        // Issue and persist new access token
        /** @phpstan-ignore-next-line */
        $accessToken = $this->issueAccessToken($accessTokenTTL, $client, $user->getIdentifier(), $finalizedScopes);
        $this->getEmitter()->emit(
            new RequestAccessTokenEvent(RequestEvent::ACCESS_TOKEN_ISSUED, $request, $accessToken)
        );
        $responseType->setAccessToken($accessToken);

        // Issue and persist new refresh token if given
        $refreshToken = $this->issueRefreshToken($accessToken);

        if ($refreshToken !== null) {
            $this->getEmitter()->emit(
                new RequestRefreshTokenEvent(RequestEvent::REFRESH_TOKEN_ISSUED, $request, $refreshToken)
            );
            $responseType->setRefreshToken($refreshToken);
        }

        $this->validateEndpointPermissions($request, $user);

        return $responseType;
    }

    /**
     * This is a hack to save 250ms by doing the work of 2 requests with just 1
     * @throws OAuthServerException
     */
    public function validateEndpointPermissions(
        ServerRequestInterface $request,
        UserEntityInterface $user,
    ): void {
        // By now the response can only be a valid token as other cases are dealt with by throwing exceptions
        $identifier = $user->getIdentifier();
        assert(is_string($identifier));
        $userId = OAuth2UserRepository::extractUserIdFromIdentifier($identifier);

        $endpoint = $this->getRequestParameter('endpoint', $request);

        if ($userId > 0) {
            if (is_null($endpoint) || $endpoint === '') {
                return;
            }
            try {
                $access = $this->permissionCalculationService->getPermission($endpoint, $userId);
                if ($access === AccessEnum::ALLOW) {
                    return;
                }
            } catch (Exception $e) {
                $this->logger->warning($e->getMessage());
            }
        }

        throw new OAuthServerException('Endpoint not allowed', 99, 'access_denied', 406);
    }
}
