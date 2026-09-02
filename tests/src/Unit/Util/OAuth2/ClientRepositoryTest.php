<?php

declare(strict_types=1);

namespace Application\Test\Unit\Util\OAuth2;

use Application\Core\Util\OAuth2\Model\PersistedClient;
use Application\Core\Util\OAuth2\Repository\ClientRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class ClientRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private ClientRepository $clientRepository;

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

        $clientRepository = $application->container()->get(ClientRepository::class);
        assert($clientRepository instanceof ClientRepository);
        $this->clientRepository = $clientRepository;
    }

    public function testStoreAndRestoresAccessToken(): void
    {
        $client = TestEntityBuilder::buildPersistedClient(['secret' => 'peekaboo']);

        $this->em->persist($client);
        $this->em->flush();
        $this->em->clear();

        $clientReloaded = $this->clientRepository->findOneBy(['identifier' => $client->identifier()]);

        self::assertInstanceOf(PersistedClient::class, $clientReloaded);
        self::assertEquals(
            $client->identifier(),
            $clientReloaded->identifier()
        );
        self::assertEquals(
            $client->name(),
            $clientReloaded->name()
        );
        self::assertEquals(
            $client->redirectUris(),
            $clientReloaded->redirectUris()
        );
        self::assertEquals(
            $client->isConfidential(),
            $clientReloaded->isConfidential()
        );
        self::assertTrue($clientReloaded->verifySecret('peekaboo'));
    }
}
