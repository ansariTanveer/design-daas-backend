<?php

namespace Application\Core\OAuth2\Repository;

use Application\Core\User\Model\Admin;
use Application\Core\User\Model\User;
use Application\Core\User\Repository\UserRepository;
use Application\Core\Util\OAuth2\OAuth2Entity\OAuth2ScopeEntity;
use Assert\Assert;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

/**
 * Returns what scopes are available for oauth2-server
 */
final readonly class OAuth2ScopeRepository implements ScopeRepositoryInterface
{
    /** @var array<string, ScopeEntityInterface> */
    public array $scopes;

    public function __construct(
        private UserRepository $repository
    ) {
        $scopes = [];

        $userScope = new OAuth2ScopeEntity();
        $userScope->setIdentifier('user');
        $scopes['user'] = $userScope;

        $adminScope = new OAuth2ScopeEntity();
        $adminScope->setIdentifier('admin');
        $scopes['admin'] = $adminScope;

        $this->scopes = $scopes;
    }

    /**
     * @param string $identifier
     */
    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface
    {
        Assert::that($identifier)->string();

        return array_key_exists($identifier, $this->scopes) ? $this->scopes[$identifier] : null;
    }

    /**
     * @param array<ScopeEntityInterface> $scopes
     * @return array<ScopeEntityInterface>
     * @throws OAuthServerException
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null
    ): array {
        Assert::thatAll($scopes)->isInstanceOf(ScopeEntityInterface::class);
        Assert::that($grantType)->string();
        Assert::that($userIdentifier)->nullOr()->string();

        $userId = OAuth2UserRepository::extractUserIdFromIdentifier((string)$userIdentifier);
        assert(is_int($userId));
        $user = $this->repository->findBaseUserById($userId);

        $applicableScopes = array_filter(
            $scopes,
            function (ScopeEntityInterface $scope) use ($user): bool {
                switch ($scope->getIdentifier()) {
                    case 'user':
                        return ($user instanceof User);
                    case 'admin':
                        return ($user instanceof Admin);
                    default:
                        throw new \RuntimeException('Unexpected scope "' . $scope->getIdentifier() . '"');
                }
            }
        );

        if (count($applicableScopes) === 0) {
            throw OAuthServerException::accessDenied('User access denied');
        }

        return $applicableScopes;
    }
}
