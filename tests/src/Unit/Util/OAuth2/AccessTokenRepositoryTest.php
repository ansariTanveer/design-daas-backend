<?php

declare(strict_types=1);

namespace Application\Test\Unit\Util\OAuth2;

use Application\Core\Util\OAuth2\Model\PersistedAccessToken;
use Application\Core\Util\OAuth2\Repository\AccessTokenRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class AccessTokenRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private AccessTokenRepository $accessTokenRepository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($application);

        $this->em = TestApplicationFactory::extractEntityManager($application);

        $accessTokenRepository = $application->container()->get(AccessTokenRepository::class);
        assert($accessTokenRepository instanceof AccessTokenRepository);
        $this->accessTokenRepository = $accessTokenRepository;
    }

    public function testStoreAndRestoresAccessToken(): void
    {
        $accessToken = TestEntityBuilder::buildPersistedAccessToken();

        $this->em->persist($accessToken);
        $this->em->flush();
        $this->em->clear();

        $accessTokenReloaded = $this->accessTokenRepository->findOneBy(['identifier' => $accessToken->identifier()]);

        self::assertInstanceOf(PersistedAccessToken::class, $accessTokenReloaded);
        self::assertEquals(
            $accessToken->identifier(),
            $accessTokenReloaded->identifier()
        );
        self::assertEquals(
            $accessToken->createMoment()->toImmutableDateTime(),
            $accessTokenReloaded->createMoment()->toImmutableDateTime()
        );
        self::assertEquals(
            $accessToken->validUntilMoment()->toImmutableDateTime(),
            $accessTokenReloaded->validUntilMoment()->toImmutableDateTime()
        );
        self::assertEquals(
            $accessToken->clientIdentifier(),
            $accessTokenReloaded->clientIdentifier()
        );
        self::assertEquals(
            $accessToken->scopes(),
            $accessTokenReloaded->scopes()
        );
        self::assertEquals(
            $accessToken->isRevoked(),
            $accessTokenReloaded->isRevoked()
        );
    }
}
