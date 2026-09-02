<?php

declare(strict_types=1);

namespace Application\Test\Unit\Util\OAuth2;

use Application\Core\Util\OAuth2\Model\PersistedRefreshToken;
use Application\Core\Util\OAuth2\Repository\RefreshTokenRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class RefreshTokenRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private RefreshTokenRepository $refreshTokenRepository;

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

        $refreshTokenRepository = $application->container()->get(RefreshTokenRepository::class);
        assert($refreshTokenRepository instanceof RefreshTokenRepository);
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function testStoreAndRestoresAccessToken(): void
    {
        $refreshToken = TestEntityBuilder::buildPersistedRefreshToken();

        $this->em->persist($refreshToken);
        $this->em->flush();
        $this->em->clear();

        $refreshTokenReloaded = $this->refreshTokenRepository->findOneBy(['identifier' => $refreshToken->identifier()]);

        self::assertInstanceOf(PersistedRefreshToken::class, $refreshTokenReloaded);
        self::assertEquals(
            $refreshToken->identifier(),
            $refreshTokenReloaded->identifier()
        );
        self::assertEquals(
            $refreshToken->createMoment()->toImmutableDateTime(),
            $refreshTokenReloaded->createMoment()->toImmutableDateTime()
        );
        self::assertEquals(
            $refreshToken->validUntilMoment()->toImmutableDateTime(),
            $refreshTokenReloaded->validUntilMoment()->toImmutableDateTime()
        );
        self::assertEquals(
            $refreshToken->accessToken(),
            $refreshTokenReloaded->accessToken()
        );
        self::assertEquals(
            $refreshToken->clientIdentifier(),
            $refreshTokenReloaded->clientIdentifier()
        );
    }
}
