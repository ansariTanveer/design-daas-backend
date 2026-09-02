<?php

namespace Application\Core\Util\OAuth2\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Util\OAuth2\Model\PersistedRefreshToken;
use Application\Core\Util\OAuth2\OAuth2Entity\OAuth2RefreshTokenEntity;
use BjoernGoetschke\DateTime\Interval;
use BjoernGoetschke\DateTime\Moment;
use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

/**
 * @extends InjectableEntityRepository<PersistedRefreshToken>
 * @template-extends InjectableEntityRepository<PersistedRefreshToken>
 */
class RefreshTokenRepository extends InjectableEntityRepository implements RefreshTokenRepositoryInterface
{
    public function deleteExpiredTokens(): void
    {
        $this->getEntityManager()->createQuery(
        /** @lang DQL */
            "DELETE FROM " . PersistedRefreshToken::class . " t WHERE t.validUntilMoment < :moment"
        )->execute(
            [
                'moment' => Moment::now()->sub(Interval::hours(6))->toImmutableDateTime(),
            ]
        );

        $this->getEntityManager()->flush();
    }

    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new OAuth2RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        $existingRefreshToken = $this->find($refreshTokenEntity->getIdentifier());
        if ($existingRefreshToken !== null) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $accessToken = PersistedRefreshToken::fromRefreshTokenEntity($refreshTokenEntity);

        $this->getEntityManager()->persist($accessToken);
        $this->getEntityManager()->flush();
    }

    public function revokeRefreshToken(/* string */ $tokenId): void
    {
        assert(is_string($tokenId));

        $refreshToken = $this->find($tokenId);

        if (!is_null($refreshToken)) {
            $refreshToken->revoke();
            $this->getEntityManager()->persist($refreshToken);
            $this->getEntityManager()->flush();
        }
    }

    public function isRefreshTokenRevoked(/* string */ $tokenId): bool
    {
        assert(is_string($tokenId));

        $refreshToken = $this->find($tokenId);
        if ($refreshToken !== null) {
            return $refreshToken->isRevoked();
        }
        return true;
    }
}
