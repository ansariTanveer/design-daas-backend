<?php

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Permissions\Model\Endpoint;
use Application\Core\Permissions\Repository\EndpointRepository;
use Application\Core\Permissions\Repository\EndpointUserAccessRepository;
use Application\Core\Permissions\Repository\EndpointUserGroupAccessRepository;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\User;
use Application\Core\User\Model\UserGroup;
use Application\Core\User\Repository\UserGroupRepository;
use Application\Core\User\Repository\UserRepository;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class EndpointTest extends TestCase
{
    private ApplicationInterface $application;
    private EntityManagerInterface $em;
    private EndpointRepository $sut;
    private EndpointUserAccessRepository $endpointUserAccessRepository;
    private EndpointUserGroupAccessRepository $endpointUserGroupAccessRepository;

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

        /** @var EndpointRepository $endpointRepository */
        $endpointRepository = $this->application->container()->get(EndpointRepository::class);
        $this->sut = $endpointRepository;

        /** @var EndpointUserAccessRepository $endpointUserAccessRepository */
        $endpointUserAccessRepository = $this->application->container()->get(EndpointUserAccessRepository::class);
        $this->endpointUserAccessRepository = $endpointUserAccessRepository;

        /** @var EndpointUserGroupAccessRepository $endpointUserGroupAccessRepository */
        $endpointUserGroupAccessRepository =
            $this->application->container()->get(EndpointUserGroupAccessRepository::class);
        $this->endpointUserGroupAccessRepository = $endpointUserGroupAccessRepository;
    }

    public function testStoresAndRestoresEndpoint(): void
    {
        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);

        $this->em->persist($endpoint);
        $this->em->flush();
        $this->em->clear();

        $endpointRestored = $this->sut->find($endpoint->id());

        self::assertInstanceOf(Endpoint::class, $endpointRestored);
        self::assertEquals($endpoint->id(), $endpointRestored->id());
        self::assertEquals($endpoint->functionName(), $endpointRestored->functionName());
        self::assertEquals($endpoint->endpointUrl(), $endpointRestored->endpointUrl());
        self::assertEquals($endpoint->method(), $endpointRestored->method());
    }

    public function testRemoveCascades(): void
    {
        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $data = $endpointFixture->load();

        self::assertCount(1, $this->endpointUserAccessRepository->findAll());
        self::assertCount(1, $this->endpointUserGroupAccessRepository->findAll());


        $this->em->remove($data->endpoint);

        $this->em->flush();
        $this->em->clear();

        self::assertCount(0, $this->endpointUserAccessRepository->findAll());
        self::assertCount(0, $this->endpointUserGroupAccessRepository->findAll());
    }
}
