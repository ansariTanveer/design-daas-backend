<?php

declare(strict_types=1);

namespace Application\Test\Unit\User;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Permissions\Repository\EndpointGroupUserGroupAccessRepository;
use Application\Core\Permissions\Repository\EndpointUserGroupAccessRepository;
use Application\Core\User\Exception\UserGroupException;
use Application\Core\User\Model\UserGroup;
use Application\Core\User\Repository\UserGroupRepository;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

class UserGroupRepositoryTest extends TestCase
{
    private ApplicationInterface $application;
    private EntityManagerInterface $em;
    private UserGroupRepository $userGroupRepository;
    private EndpointUserGroupAccessRepository $endpointUserGroupAccessRepository;

    private EndpointGroupUserGroupAccessRepository $endpointGroupUserGroupAccessRepository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $userGroupRepository = $this->application->container()->get(UserGroupRepository::class);
        assert($userGroupRepository instanceof UserGroupRepository);
        $this->userGroupRepository = $userGroupRepository;

        /** @var EndpointUserGroupAccessRepository $endpointUserGroupAccessRepository */
        $endpointUserGroupAccessRepository =
            $this->application->container()->get(EndpointUserGroupAccessRepository::class);
        $this->endpointUserGroupAccessRepository = $endpointUserGroupAccessRepository;

        /** @var EndpointGroupUserGroupAccessRepository $endpointGroupUserGroupAccessRepository */
        $endpointGroupUserGroupAccessRepository =
            $this->application->container()->get(EndpointGroupUserGroupAccessRepository::class);
        $this->endpointGroupUserGroupAccessRepository = $endpointGroupUserGroupAccessRepository;
    }

    public function testStoreAndRestoreUserGroupEntity(): void
    {
        $userGroup = TestEntityBuilder::buildUserGroup();

        $this->em->persist($userGroup);
        $this->em->flush();
        $this->em->clear();

        $userGroupRestored = $this->userGroupRepository->find($userGroup->id());

        self::assertInstanceOf(UserGroup::class, $userGroupRestored);
        self::assertSame($userGroup->id(), $userGroupRestored->id());
        self::assertSame($userGroup->description(), $userGroupRestored->description());
    }

    public function testUsersCanBeAddedAndRemovedToUserGroup(): void
    {
        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $userOne = TestEntityBuilder::buildUser();
        $this->em->persist($userOne);
        $userTwo = TestEntityBuilder::buildUser();
        $this->em->persist($userTwo);

        $this->em->flush();

        $userGroup->addUser($userOne);
        $userGroup->addUser($userTwo);

        $this->em->flush();
        $this->em->clear();

        $userGroupReloaded = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupReloaded);
        self::assertCount(2, $userGroupReloaded->users());

        $userReloaded = $userGroupReloaded->users()[0];
        self::assertEquals($userOne->id(), $userReloaded->id());
        self::assertCount(1, $userReloaded->groups());
        self::assertEquals($userGroupReloaded->id(), $userReloaded->groups()[0]->id());

        $userGroupReloaded->removeUser($userGroupReloaded->users()[0]);

        $this->em->flush();
        $this->em->clear();

        $userGroupReReloaded = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupReReloaded);
        self::assertCount(1, $userGroupReReloaded->users());
    }

    /**
     * @throws Exception
     */
    public function testCanAddAndRemoveDesktopGroups(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addDesktopGroup($desktopGroup);
        $this->em->persist($userGroup);

        $this->em->flush();
        $this->em->clear();

        $userGroupRestored = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupRestored);
        self::assertCount(1, $userGroupRestored->desktopGroups());
        self::assertEquals($desktopGroup->id(), $desktopGroup->userGroups()[0]->id());

        $userGroupRestored->removeDesktopGroup($userGroupRestored->desktopGroups()[0]);

        $this->em->flush();
        $this->em->clear();

        $userGroupReRestored = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupReRestored);
        self::assertCount(0, $userGroupReRestored->desktopGroups());

        $manyToManyAssociations = $this->em->getConnection()->fetchAllAssociative(
        /** @lang MySQL */
            'SELECT * FROM desktopgroup_usergroup'
        );
        self::assertCount(0, $manyToManyAssociations);
    }

    public function testRemoveCascades(): void
    {
        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $data = $endpointFixture->load();

        self::assertCount(1, $this->endpointUserGroupAccessRepository->findAll());
        self::assertCount(1, $this->endpointGroupUserGroupAccessRepository->findAll());

        $this->em->remove($data->userGroup);

        $this->em->flush();
        $this->em->clear();

        self::assertCount(0, $this->endpointUserGroupAccessRepository->findAll());
        self::assertCount(0, $this->endpointGroupUserGroupAccessRepository->findAll());
    }


    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFindBasUserIds(): void
    {
        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $userGroup2 = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup2);

        $userGroup3 = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup3);

        $this->em->flush();
        $this->em->clear();

        $userGroupsRestored = $this->userGroupRepository->findGroups(
            [$userGroup->id(), $userGroup3->id()]
        );

        self::assertCount(2, $userGroupsRestored);
        self::assertEquals($userGroup->id(), $userGroupsRestored[0]->id());
        self::assertEquals($userGroup3->id(), $userGroupsRestored[1]->id());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFindBasUserIdsFailsOnInvalidId(): void
    {
        self::expectException(UserGroupException::class);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $userGroup2 = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup2);

        $this->em->flush();
        $this->em->clear();

        $this->userGroupRepository->findGroups(
            [$userGroup->id(), 1234, $userGroup2->id()]
        );
    }
}
