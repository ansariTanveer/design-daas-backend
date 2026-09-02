<?php

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Model\EndpointUserAccess;
use Application\Core\Permissions\Repository\EndpointUserAccessRepository;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class EndpointUserAccessTest extends TestCase
{
    private ApplicationInterface $application;
    private EntityManagerInterface $em;

    private EndpointUserAccessRepository $sut;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        /** @var EndpointUserAccessRepository $sut */
        $sut = $this->application->container()->get(EndpointUserAccessRepository::class);
        $this->sut = $sut;
    }

    public function testStoresAndRestoresEndpoint(): void
    {
        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $endpointUserAccess = TestEntityBuilder::buildEndpointUserAccess(
            $endpoint,
            $user
        );
        $this->em->persist($endpointUserAccess);

        $this->em->flush();
        $this->em->clear();

        $endpointUserAccessRestored = $this->sut->find($endpointUserAccess->id());

        self::assertInstanceOf(EndpointUserAccess::class, $endpointUserAccessRestored);
        self::assertEquals($endpointUserAccess->id(), $endpointUserAccessRestored->id());
        self::assertEquals($endpointUserAccess->endpoint()->id(), $endpointUserAccessRestored->endpoint()->id());
        self::assertEquals($endpointUserAccess->user()->id(), $endpointUserAccessRestored->user()->id());
        self::assertEquals($endpointUserAccess->relation(), $endpointUserAccess->relation());
    }

    public function testOnlyOneRelationPerEndpointAndUser(): void
    {
        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $data = $endpointFixture->load();

        self::expectException(UniqueConstraintViolationException::class);

        $endpointUserAccess2 = TestEntityBuilder::buildEndpointUserAccess(
            $data->endpoint,
            $data->user,
            ['relation' => AccessEnum::DENY]
        );
        $this->em->persist($endpointUserAccess2);

        $this->em->flush();
        $this->em->clear();
    }
}
