<?php

namespace Application\Core\OAuth2;

use Application\Core\OAuth2\Exception\OAuth2AuthorizationServerException;
use Application\Core\OAuth2\Repository\OAuth2UserRepository;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Repository\UserRepository;
use Application\Core\Util\OAuth2\Repository\AccessTokenRepository;
use DI\Annotation\Inject;
use League\OAuth2\Server\AuthorizationValidators\BearerTokenValidator;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use OutOfBoundsException;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

/**
 * Handles "oauth2-server" scope. Called by OAuth2AccessControlMiddleware.
 */
final class OAuth2AuthorizationValidator extends BearerTokenValidator
{
    public const USER_ID_ATTRIBUTE = 'user_id';
    public const USER_ATTRIBUTE = 'user';
    private UserRepository $repository;
    private ?string $testTokenPrefix;

    /**
     * @Inject({
     *     "publicKeyFile" = "config.oauth2.public_key_file",
     *     "testTokenPrefix" = "config.oauth2.user_test_token_prefix",
     * })
     */
    public function __construct(
        AccessTokenRepository $accessTokenRepository,
        string $publicKeyFile,
        UserRepository $repository,
        ?string $testTokenPrefix,
    ) {
        parent::__construct($accessTokenRepository);
        try {
            $this->setPublicKey(new CryptKey($publicKeyFile, null, false));
        } catch (Throwable $e) {
            throw OAuth2AuthorizationServerException::failedToGenerateCryptKey($e);
        }
        $this->repository = $repository;
        $this->testTokenPrefix = $testTokenPrefix;
    }

    /**
     * @throws OAuthServerException
     */
    public function validateAuthorization(ServerRequestInterface $request): ServerRequestInterface
    {
        /*
         * Attributes set by validator:
         * - oauth_access_token_id (string|null)
         * - oauth_client_id (string|null)
         * - oauth_user_id (string|null)
         * - oauth_scopes (array of strings)
         */
        if (null !== $testToken = $this->parseTestToken($request)) {
            // In tests, inject token from TestCase at this time.
            $authorizedRequest = $request
                ->withAttribute('oauth_access_token_id', array_shift($testToken))
                ->withAttribute('oauth_client_id', array_shift($testToken))
                ->withAttribute('oauth_user_id', array_shift($testToken))
                ->withAttribute('oauth_scopes', array_shift($testToken));
            $userId = array_shift($testToken);
        } else {
            // Otherwise parse token in HTTP request
            $authorizedRequest = parent::validateAuthorization($request);
            $identifier = $authorizedRequest->getAttribute('oauth_user_id');
            assert(is_string($identifier));
            $userId = OAuth2UserRepository::extractUserIdFromIdentifier($identifier);
        }

        if ($userId === null) {
            // Access token not accepted by this endpoint
            return $request;
        }

        // Find user referred to in token
        $user = $this->repository->findBaseUserById((int)$userId);
        if (is_null($user)) {
            // No such user
            throw OAuthServerException::accessDenied('User is no longer valid');
        }

        if (!$user->enabled()) {
            throw OAuthServerException::accessDenied('User disabled');
        }

        // Inject userId and user into request. May be extracted later for userId and userOfRequest
        return $authorizedRequest
            ->withAttribute(self::USER_ID_ATTRIBUTE, $user->id())
            ->withAttribute(self::USER_ATTRIBUTE, $user);
    }

    /**
     * @return array<string|null|array<string>>|null
     */
    private function parseTestToken(ServerRequestInterface $request): ?array
    {
        if (is_null($this->testTokenPrefix) || !$request->hasHeader('authorization')) {
            return null;
        }

        $header = $request->getHeader('authorization');
        $token = trim((string)preg_replace('/^(?:\s+)?Bearer\s/', '', $header[0]));

        $pattern = '=^' . preg_quote($this->testTokenPrefix, '=') . ':([0-9]+):([\w\-.[:space:]]*)$=';

        $regExResult = preg_match($pattern, $token, $matches);
        assert(is_int($regExResult));
        if ($regExResult === 0) {
            return null;
        }

        // ???
        array_shift($matches);
        return [
            $token,
            null,
            implode(':', $matches),
            array_values(
                array_filter(
                    array_unique(explode(' ', $matches[1]), SORT_STRING),
                    'strlen' // @phpstan-ignore-line
                )
            ),
            $matches[0]
        ];
    }

    /**
     * @deprecated Use "userOfRequest" instead
     */
    public static function userId(ServerRequestInterface $request): ?int
    {
        $userId = $request->getAttribute(self::USER_ID_ATTRIBUTE);
        return (is_int($userId)) ? $userId : null;
    }


    public static function userOfRequest(ServerRequestInterface $request): BaseUser
    {
        $user = $request->getAttribute(self::USER_ATTRIBUTE);
        if (!$user instanceof BaseUser) {
            throw new OutOfBoundsException('Endpoint expects a logged in user but has no security defined');
        }

        return $user;
    }

    public function tokenInfo(ServerRequestInterface $request, string $jwt): ?int
    {
        $request = $request->withHeader('authorization', ['Bearer ' . $jwt]);

        try {
            $authorizedRequest = parent::validateAuthorization($request);
        } catch (Throwable $e) {
            return null;
        }

        $identifier = $authorizedRequest->getAttribute('oauth_user_id');
        assert(is_string($identifier));

        return OAuth2UserRepository::extractUserIdFromIdentifier($identifier);
    }
}
