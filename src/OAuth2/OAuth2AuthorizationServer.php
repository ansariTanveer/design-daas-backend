<?php

namespace Application\Core\OAuth2;

use Application\Core\OAuth2\Exception\OAuth2AuthorizationServerException;
use Application\Core\OAuth2\Repository\OAuth2ScopeRepository;
use Application\Core\OAuth2\Repository\OAuth2UserRepository;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Service\PermissionCalculationService;
use Application\Core\Util\OAuth2\Repository\AccessTokenRepository;
use Application\Core\Util\OAuth2\Repository\ClientRepository;
use Application\Core\Util\OAuth2\Repository\RefreshTokenRepository;
use Application\Core\Util\OAuth2\VerifyingRefreshTokenGrant;
use BjoernGoetschke\DateTime\Interval;
use Defuse\Crypto\Key;
use DI\Annotation\Inject;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\PasswordGrant;
use Monolog\Logger;
use OutOfBoundsException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use stdClass;
use Throwable;

/**
 * Sets up "oauth2-server" scope.
 */
final class OAuth2AuthorizationServer extends AuthorizationServer
{
    /**
     * @Inject({
     *     "privateKeyFile" = "config.oauth2.private_key_file",
     *     "encryptionKeyFile" = "config.oauth2.encryption_key_file",
     *     "refreshTokenTTL" = "config.oauth2.refresh_token_ttl_in_seconds",
     *     "accessTokenTTL" = "config.oauth2.access_token_ttl_in_seconds",
     * })
     * @param ClientRepository $clientRepository
     * @param OAuth2ScopeRepository $scopeRepository
     * @param OAuth2UserRepository $userRepository
     * @param RefreshTokenRepository $refreshTokenRepository
     * @param AccessTokenRepository $accessTokenRepository
     * @param string $privateKeyFile
     * @param string $encryptionKeyFile
     * @param int $refreshTokenTTL
     * @param int $accessTokenTTL
     * @param PermissionCalculationService $permissionCalculationService
     */
    public function __construct(
        ClientRepository $clientRepository,
        OAuth2ScopeRepository $scopeRepository,
        OAuth2UserRepository $userRepository,
        RefreshTokenRepository $refreshTokenRepository,
        AccessTokenRepository $accessTokenRepository,
        string $privateKeyFile,
        string $encryptionKeyFile,
        int $refreshTokenTTL,
        int $accessTokenTTL,
        PermissionCalculationService $permissionCalculationService,
        LoggerInterface $logger,
    ) {
        try {
            parent::__construct(
                $clientRepository,
                $accessTokenRepository,
                $scopeRepository,
                new CryptKey($privateKeyFile, null, false),
                Key::loadFromAsciiSafeString((string)file_get_contents($encryptionKeyFile))
            );
        } catch (Throwable $e) {
            throw OAuth2AuthorizationServerException::failedToGenerateCryptKey($e);
        }

        $refreshTokenTTLInterval = Interval::seconds($refreshTokenTTL)->toDateInterval();
        $accessTokenTTLInterval = Interval::seconds($accessTokenTTL)->toDateInterval();

        $passwordGrant = new OAuth2PasswordGrant(
            $userRepository,
            $refreshTokenRepository,
            $permissionCalculationService,
            $logger
        );
        $passwordGrant->setRefreshTokenTTL($refreshTokenTTLInterval);
        $this->enableGrantType($passwordGrant, $accessTokenTTLInterval);

        $refreshTokenGrant = new VerifyingRefreshTokenGrant($refreshTokenRepository, $userRepository);
        $refreshTokenGrant->setRefreshTokenTTL($refreshTokenTTLInterval);
        $this->enableGrantType($refreshTokenGrant, $accessTokenTTLInterval);
    }
}
