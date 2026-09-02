<?php

declare(strict_types=1);

namespace Application\Test\Unit\Desktop;

use Application\Core\Desktop\Exception\DesktopGroupException;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\Desktop\Repository\DesktopGroupRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

final class DesktopGroupRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private DesktopGroupRepository $desktopGroupRepository;

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

        $desktopGroupRepository = $application->container()->get(DesktopGroupRepository::class);
        assert($desktopGroupRepository instanceof DesktopGroupRepository);
        $this->desktopGroupRepository = $desktopGroupRepository;
    }

    public function testStoreAndRestoresDesktop(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $this->em->flush();
        $this->em->clear();

        $desktopGroupRestored = $this->desktopGroupRepository->find($desktopGroup->id());

        self::assertInstanceOf(DesktopGroup::class, $desktopGroupRestored);
        self::assertEquals($desktopGroup->id(), $desktopGroupRestored->id());
        self::assertEquals($desktopGroup->description(), $desktopGroupRestored->description());
    }

    public function testCanAddAndRemoveDesktops(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $desktop = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktop);
        $desktopGroup->addDesktop($desktop);

        $this->em->flush();
        $this->em->clear();

        $desktopGroupRestored = $this->desktopGroupRepository->find($desktopGroup->id());
        self::assertInstanceOf(DesktopGroup::class, $desktopGroupRestored);
        self::assertCount(1, $desktopGroupRestored->desktops());
        self::assertEquals($desktop->id(), $desktopGroupRestored->desktops()[0]->id());

        $desktopGroupRestored->removeDesktop($desktopGroupRestored->desktops()[0]);

        $this->em->flush();
        $this->em->clear();

        $desktopGroupReRestored = $this->desktopGroupRepository->find($desktopGroup->id());
        self::assertInstanceOf(DesktopGroup::class, $desktopGroupReRestored);
        self::assertCount(0, $desktopGroupReRestored->desktops());
    }

    public function testCantAddDesktopAlreadyAdded(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $desktop = TestEntityBuilder::buildDesktop();

        $desktopGroup->addDesktop($desktop);

        self::expectException(DesktopException::class);
        $desktopGroup->addDesktop($desktop);
    }

    public function testCantRemoveDesktopNotInGroup(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $desktop = TestEntityBuilder::buildDesktop();

        self::expectException(DesktopException::class);
        $desktopGroup->removeDesktop($desktop);
    }

    /**
     * @throws Exception
     */
    public function testCanAddAndRemoveUserGroups(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);
        $desktopGroup->addUserGroup($userGroup);

        $this->em->flush();
        $this->em->clear();

        $desktopGroupRestored = $this->desktopGroupRepository->find($desktopGroup->id());
        self::assertInstanceOf(DesktopGroup::class, $desktopGroupRestored);
        self::assertCount(1, $desktopGroupRestored->userGroups());
        self::assertEquals($userGroup->id(), $desktopGroupRestored->userGroups()[0]->id());

        $desktopGroupRestored->removeUserGroup($desktopGroupRestored->userGroups()[0]);

        $this->em->flush();
        $this->em->clear();

        $desktopGroupReRestored = $this->desktopGroupRepository->find($desktopGroup->id());
        self::assertInstanceOf(DesktopGroup::class, $desktopGroupReRestored);
        self::assertCount(0, $desktopGroupReRestored->userGroups());

        $manyToManyAssociations = $this->em->getConnection()->fetchAllAssociative(
            /** @lang MySQL */
            'SELECT * FROM desktopgroup_usergroup'
        );
        self::assertCount(0, $manyToManyAssociations);
    }

    public function testCantAddUserGroupAlreadyAdded(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $userGroup = TestEntityBuilder::buildUserGroup();

        $desktopGroup->addUserGroup($userGroup);

        self::expectException(DesktopGroupException::class);
        $desktopGroup->addUserGroup($userGroup);
    }

    public function testCantRemoveUserGroupNotInGroup(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $userGroup = TestEntityBuilder::buildUserGroup();

        self::expectException(DesktopGroupException::class);
        $desktopGroup->removeUserGroup($userGroup);
    }
}
