<?php

namespace Application\Test\Unit\Permissions;

use Application\Core\Permissions\Exception\EndpointGroupException;
use Application\Core\Permissions\Exception\EndpointGroupUserGroupAccessException;
use Application\Core\Permissions\Model\EndpointGroupUserGroupAccess;
use Application\Core\Permissions\Repository\EndpointGroupUserGroupAccessRepository;
use Application\Core\Permissions\Service\EndpointGroupService;
use Application\Core\User\Exception\UserGroupException;
use Application\Test\TestApplicationFactory;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class EndpointGroupServiceTest extends TestCase
{
    private EndpointGroupService $sut;
    private EntityManagerInterface $em;

    private EndpointGroupUserGroupAccessRepository $repository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($application);

        $this->em = TestApplicationFactory::extractEntityManager($application);

        /** @var EndpointGroupService $sut */
        $sut = $application->container()->get(EndpointGroupService::class);
        $this->sut = $sut;

        /** @var EndpointGroupUserGroupAccessRepository $sut */
        $repository = $application->container()->get(EndpointGroupUserGroupAccessRepository::class);
        assert($repository instanceof EndpointGroupUserGroupAccessRepository);
        $this->repository = $repository;
    }

    /**
     * @throws EndpointGroupException
     * @throws EndpointGroupUserGroupAccessException
     * @throws UserGroupException
     */
    public function testDeleteEndpointGroupUserGroupAccess(): void
    {
        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $userGroup = TestEntityBuilder::buildUserGroup();
        $endpointGroupUserGroup = TestEntityBuilder::buildEndpointGroupUserGroupAccess(
            $endpointGroup,
            $userGroup
        );

        $this->em->persist($endpointGroup);
        $this->em->persist($userGroup);
        $this->em->persist($endpointGroupUserGroup);
        $this->em->flush();
        $this->em->clear();

        $check = $this->repository->find($endpointGroupUserGroup->id());
        self::assertInstanceOf(EndpointGroupUserGroupAccess::class, $check);
        self::assertEquals($endpointGroupUserGroup->id(), $check->id());
        self::assertEquals($userGroup->id(), $check->userGroup()->id());

        $this->sut->deleteEndpointGroupUserGroupAccess($endpointGroup->uniqueGroupName(), $userGroup->id());
        $this->em->flush();
        $this->em->clear();

        $check = $this->repository->find($endpointGroupUserGroup->id());
        self::assertNull($check);
    }
}
