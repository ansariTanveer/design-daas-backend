<?php

declare(strict_types=1);

namespace Application\Test\Unit\User;

use Application\Core\User\DTO\UserGroupUpdateDTO;
use Application\Core\User\Enum\AssociateDesktopGroupResult;
use Application\Core\User\Exception\UserGroupServiceException;
use Application\Core\User\Model\UserGroup;
use Application\Core\User\Repository\UserGroupRepository;
use Application\Core\User\Service\UserGroupService;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class UserGroupServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserGroupRepository $userGroupRepository;
    private UserGroupService $userGroupService;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($application);

        $em = $application->container()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        $this->em = $em;

        $userGroupRepository = $application->container()->get(UserGroupRepository::class);
        assert($userGroupRepository instanceof UserGroupRepository);
        $this->userGroupRepository = $userGroupRepository;

        $userGroupService = $application->container()->get(UserGroupService::class);
        assert($userGroupService instanceof UserGroupService);
        $this->userGroupService = $userGroupService;
    }

    public function testFailCreateGroupWithEmptyDescription(): void
    {

        self::expectException(UserGroupServiceException::class);

        $this->userGroupService->createGroup('');
    }

    public function testCreateGroup(): void
    {
        $userGroup = $this->userGroupService->createGroup('Some description');
        $this->em->flush();

        $reloaded = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $reloaded);
        self::assertSame('Some description', $reloaded->description());
    }

    public function testAssociateDesktopGroupAddsDesktopGroup(): void
    {
        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $this->em->flush();
        $this->em->clear();

        $resultObject = $this->userGroupService->associateDesktopGroup(
            $userGroup->id(),
            $desktopGroup->id()
        );

        self::assertEquals(AssociateDesktopGroupResult::OK, $resultObject->result);
        self::assertIsArray($resultObject->updatedList);
        self::assertCount(1, $resultObject->updatedList);

        self::assertEquals($desktopGroup->id(), $resultObject->updatedList[0]->id);
        self::assertEquals($desktopGroup->description(), $resultObject->updatedList[0]->description);

        $this->em->clear();
        $userGroupReloaded = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupReloaded);
        self::assertCount(1, $userGroupReloaded->desktopGroups());
    }

    public function testAssociateDesktopGroupFailsOnInvalidUserGroup(): void
    {
        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $this->em->flush();
        $this->em->clear();

        $resultObject = $this->userGroupService->associateDesktopGroup(
            22,
            $desktopGroup->id()
        );

        self::assertEquals(AssociateDesktopGroupResult::INVALID_USER_GROUP, $resultObject->result);
        self::assertNull($resultObject->updatedList);

        $this->em->clear();
        $userGroupReloaded = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupReloaded);
        self::assertCount(0, $userGroupReloaded->desktopGroups());
    }

    public function testAssociateDesktopGroupFailsOnInvalidDesktopGroup(): void
    {
        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $this->em->flush();
        $this->em->clear();

        $resultObject = $this->userGroupService->associateDesktopGroup(
            $userGroup->id(),
            888
        );
        self::assertEquals(AssociateDesktopGroupResult::INVALID_DESKTOP_GROUP, $resultObject->result);
        self::assertNull($resultObject->updatedList);

        $this->em->clear();
        $userGroupReloaded = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupReloaded);
        self::assertCount(0, $userGroupReloaded->desktopGroups());
    }

    public function testAssociateDesktopGroupFailsOnAlreadyInDesktopGroup(): void
    {
        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $userGroup->addDesktopGroup($desktopGroup);
        $this->em->persist($desktopGroup);

        $this->em->flush();
        $this->em->clear();

        $resultObject = $this->userGroupService->associateDesktopGroup(
            $userGroup->id(),
            $desktopGroup->id()
        );
        self::assertEquals(
            AssociateDesktopGroupResult::DESKTOP_GROUP_ALREADY_IN_USER_GROUP,
            $resultObject->result
        );
        self::assertNull($resultObject->updatedList);

        $this->em->clear();
        $userGroupReloaded = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupReloaded);
        self::assertCount(1, $userGroupReloaded->desktopGroups());
    }


    /**
     * @noinspection PhpUnhandledExceptionInspection
     */
    public function testUpdateGroup(): void
    {
        $userToRemain = TestEntityBuilder::buildUser();
        $this->em->persist($userToRemain);

        $userToBeRemoved = TestEntityBuilder::buildUser();
        $this->em->persist($userToBeRemoved);

        $userToBeAdded = TestEntityBuilder::buildUser();
        $this->em->persist($userToBeAdded);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($userToRemain);
        $userGroup->addUser($userToBeRemoved);
        $this->em->persist($userGroup);

        $this->em->flush();
        $this->em->clear();

        $requestDto = new UserGroupUpdateDTO();
        $requestDto->userIds = [$userToRemain->id(), $userToBeAdded->id()];
        $requestDto->description = 'updated_description';

        $this->userGroupService->updateGroup($userGroup->id(), $requestDto);

        $this->em->clear();

        $userGroupReloaded = $this->userGroupRepository->find($userGroup->id());
        self::assertInstanceOf(UserGroup::class, $userGroupReloaded);

        self::assertCount(2, $userGroupReloaded->users());
        self::assertTrue($userGroupReloaded->hasUser($userToRemain));
        self::assertFalse($userGroupReloaded->hasUser($userToBeRemoved));
        self::assertTrue($userGroupReloaded->hasUser($userToBeAdded));

        self::assertEquals('updated_description', $userGroupReloaded->description());
    }
}
