<?php

declare(strict_types=1);

namespace Application\Test\Unit\User;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Permissions\Repository\EndpointGroupUserAccessRepository;
use Application\Core\Permissions\Repository\EndpointUserAccessRepository;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Model\Admin;
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

class UserRepositoryTest extends TestCase
{
    private ApplicationInterface $application;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserGroupRepository $userGroupRepository;
    private EndpointUserAccessRepository $endpointUserAccessRepository;
    private EndpointGroupUserAccessRepository $endpointGroupUserAccessRepository;

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

        $userRepository = $this->application->container()->get(UserRepository::class);
        assert($userRepository instanceof UserRepository);
        $this->userRepository = $userRepository;

        $userGroupRepository = $this->application->container()->get(UserGroupRepository::class);
        assert($userGroupRepository instanceof UserGroupRepository);
        $this->userGroupRepository = $userGroupRepository;

        /** @var EndpointUserAccessRepository $endpointUserAccessRepository */
        $endpointUserAccessRepository = $this->application->container()->get(EndpointUserAccessRepository::class);
        $this->endpointUserAccessRepository = $endpointUserAccessRepository;

        /** @var EndpointGroupUserAccessRepository $endpointGroupUserAccessRepository */
        $endpointGroupUserAccessRepository =
            $this->application->container()->get(EndpointGroupUserAccessRepository::class);
        $this->endpointGroupUserAccessRepository = $endpointGroupUserAccessRepository;
    }

    public function testStoreAndRestoreUsers(): void
    {
        $user = TestEntityBuilder::buildUser();
        $admin = TestEntityBuilder::buildAdmin();

        $this->em->persist($user);
        $this->em->persist($admin);
        $this->em->flush();
        $this->em->clear();

        $userRestored = $this->userRepository->findUserById($user->id());
        self::assertInstanceOf(User::class, $userRestored);
        self::assertSame($user->id(), $userRestored->id());
        self::assertSame($user->name(), $userRestored->name());
        self::assertFalse($user->enabled());
        self::assertTrue(strlen($userRestored->registrationCode()) === BaseUser::REGISTRATION_CODE_LENGTH);

        $adminRestored = $this->userRepository->findAdminById($admin->id());
        self::assertInstanceOf(Admin::class, $adminRestored);
        self::assertSame($admin->id(), $adminRestored->id());
        self::assertSame($admin->name(), $adminRestored->name());
        self::assertTrue($admin->enabled());
    }

    public function testUserGroupsCanBeAddedAndRemovedFromUser(): void
    {
        $userGroup1 = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup1);

        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $this->em->flush();

        $user->addGroup($userGroup1);

        $this->em->flush();
        $this->em->clear();

        $userReloaded =  $this->userRepository->findUserById($user->id());
        self::assertInstanceOf(User::class, $userReloaded);
        self::assertCount(1, $userReloaded->groups());

        $userGroup2 = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup2);
        $this->em->flush();

        $userReloaded->addGroup($userGroup2);
        $this->em->persist($userReloaded);

        $this->em->flush();
        $this->em->clear();

        $userReloaded =  $this->userRepository->findUserById($user->id());
        self::assertInstanceOf(User::class, $userReloaded);
        self::assertCount(2, $userReloaded->groups());

        $userGroup1 = $this->userGroupRepository->find($userGroup1->id());
        self::assertInstanceOf(UserGroup::class, $userGroup1);

        $userReloaded->removeGroup($userGroup1);

        $this->em->flush();
        $this->em->clear();

        $userReloaded =  $this->userRepository->findUserById($user->id());
        self::assertInstanceOf(User::class, $userReloaded);
        self::assertCount(1, $userReloaded->groups());

        self::assertSame($userGroup2->id(), $userReloaded->groups()[0]->id());
    }

    public function testFindAllUsers(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $user2 = TestEntityBuilder::buildUser();
        $this->em->persist($user2);

        $admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($admin);

        $user3 = TestEntityBuilder::buildUser();
        $this->em->persist($user3);

        $user4 = TestEntityBuilder::buildUser();
        $this->em->persist($user4);

        $this->em->flush();
        $this->em->clear();

        $result = $this->userRepository->findAllUsers(null, 1);
        self::assertCount(3, $result);
        self::assertEquals($user2->id(), $result[0]->id());
        self::assertEquals($user3->id(), $result[1]->id());
        self::assertEquals($user4->id(), $result[2]->id());
    }

    public function testFindAllUsersWithLimit(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $user2 = TestEntityBuilder::buildUser();
        $this->em->persist($user2);

        $admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($admin);

        $user3 = TestEntityBuilder::buildUser();
        $this->em->persist($user3);

        $user4 = TestEntityBuilder::buildUser();
        $this->em->persist($user4);

        $this->em->flush();
        $this->em->clear();

        $result = $this->userRepository->findAllUsers(2, 1);
        self::assertCount(2, $result);
        self::assertEquals($user2->id(), $result[0]->id());
        self::assertEquals($user3->id(), $result[1]->id());
    }

    public function testRemoveCascades(): void
    {
        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $data = $endpointFixture->load();

        self::assertCount(1, $this->endpointUserAccessRepository->findAll());
        self::assertCount(1, $this->endpointGroupUserAccessRepository->findAll());

        $this->em->remove($data->user);

        $this->em->flush();
        $this->em->clear();

        self::assertCount(0, $this->endpointUserAccessRepository->findAll());
        self::assertCount(0, $this->endpointGroupUserAccessRepository->findAll());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFindBasUserIds(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $user2 = TestEntityBuilder::buildUser();
        $this->em->persist($user2);

        $admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($admin);

        $this->em->flush();
        $this->em->clear();

        $usersRestored = $this->userRepository->findBaseUsers(
            [$user->id(), $user2->id(), $admin->id()]
        );

        self::assertCount(3, $usersRestored);
        self::assertEquals($user->id(), $usersRestored[0]->id());
        self::assertEquals($user2->id(), $usersRestored[1]->id());
        self::assertEquals($admin->id(), $usersRestored[2]->id());
    }

    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testFindBasUserIdsFailsOnInvalidId(): void
    {
        self::expectException(BaseUserException::class);

        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($admin);

        $this->em->flush();
        $this->em->clear();

        $this->userRepository->findBaseUsers(
            [$user->id(), 4711, $admin->id()]
        );
    }
}
