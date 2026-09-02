<?php

namespace Application\Test\Unit\Util\OAuth2;

use Application\Core\Util\OAuth2\Repository\AccessTokenRepository;
use Application\Core\Util\OAuth2\Repository\RefreshTokenRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use BjoernGoetschke\DateTime\Interval;
use BjoernGoetschke\DateTime\Moment;
use Doctrine\ORM\EntityManagerInterface;

class TokenCleanupTest extends TestCase
{
    private EntityManagerInterface $em;
    private AccessTokenRepository $accessTokenRepository;
    private RefreshTokenRepository $refreshTokenRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($application);

        $this->em = TestApplicationFactory::extractEntityManager($application);

        /** @var AccessTokenRepository $accessTokenRepository */
        $accessTokenRepository = $application->container()->get(AccessTokenRepository::class);
        $this->accessTokenRepository = $accessTokenRepository;

        /** @var RefreshTokenRepository $refreshTokenRepository */
        $refreshTokenRepository = $application->container()->get(RefreshTokenRepository::class);
        $this->refreshTokenRepository = $refreshTokenRepository;
    }

    public function testCleanup(): void
    {
        $oldMoment = Moment::now()->sub(Interval::days(10));
        Moment::setNow($oldMoment);
        $accessToken = TestEntityBuilder::buildPersistedAccessToken();
        $this->em->persist($accessToken);

        $refreshToken = TestEntityBuilder::buildPersistedRefreshToken();
        $this->em->persist($refreshToken);
        $this->em->flush();
        $this->em->clear();

        $tokens = $this->accessTokenRepository->findAll();
        self::assertCount(1, $tokens);
        $tokens = $this->refreshTokenRepository->findAll();
        self::assertCount(1, $tokens);

        Moment::resetNow();

        $this->accessTokenRepository->deleteExpiredTokens();
        $this->refreshTokenRepository->deleteExpiredTokens();

        $tokens = $this->accessTokenRepository->findAll();
        self::assertCount(0, $tokens);
        $tokens = $this->refreshTokenRepository->findAll();
        self::assertCount(0, $tokens);
    }
}
