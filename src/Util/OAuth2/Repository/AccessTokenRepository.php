<?php

namespace Application\Core\Util\OAuth2\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Util\OAuth2\Model\PersistedAccessToken;
use Application\Core\Util\OAuth2\OAuth2Entity\OAuth2AccessTokenEntity;
use Assert\Assert;
use BjoernGoetschke\DateTime\Interval;
use BjoernGoetschke\DateTime\Moment;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

/**
 * @extends InjectableEntityRepository<PersistedAccessToken>
 * @template-extends InjectableEntityRepository<PersistedAccessToken>
 */
class AccessTokenRepository extends InjectableEntityRepository implements AccessTokenRepositoryInterface
{
    public function deleteExpiredTokens(): void
    {
        $this->getEntityManager()->createQuery(
        /** @lang DQL */
            "DELETE FROM " . PersistedAccessToken::class . " t WHERE t.validUntilMoment < :moment"
        )->execute(
            [
                'moment' => Moment::now()->sub(Interval::hours(6))->toImmutableDateTime(),
            ]
        );
    }

    public function getNewToken(
        ClientEntityInterface $clientEntity,
        /* ScopeEntityInterface[] */ array $scopes,
        /* ?string */ $userIdentifier = null
    ): AccessTokenEntityInterface {
        Assert::thatAll($scopes)->isInstanceOf(ScopeEntityInterface::class);
        Assert::that($userIdentifier)->nullOr()->string();
        assert(is_null($userIdentifier) || is_string($userIdentifier));

        $accessTokenEntity = new OAuth2AccessTokenEntity();
        $accessTokenEntity->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessTokenEntity->addScope($scope);
        }
        $accessTokenEntity->setUserIdentifier($userIdentifier);

        return $accessTokenEntity;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $existingAccessToken = $this->find($accessTokenEntity->getIdentifier());
        if ($existingAccessToken !== null) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $accessToken = PersistedAccessToken::fromAccessToken($accessTokenEntity);

        $this->getEntityManager()->persist($accessToken);
        $this->getEntityManager()->flush();
    }

    public function revokeAccessToken(/* string */ $tokenId): void
    {
        Assert::that($tokenId)->string();

        $accessToken = $this->find($tokenId);
        if (!is_null($accessToken)) {
            $accessToken->revoke();
            $this->getEntityManager()->persist($accessToken);
            $this->getEntityManager()->flush();
        }
    }

    public function isAccessTokenRevoked(/* string */ $tokenId): bool
    {
        Assert::that($tokenId)->string();

        $accessToken = $this->find($tokenId);
        if ($accessToken !== null) {
            return $accessToken->isRevoked();
        }
        return true;
    }
}
