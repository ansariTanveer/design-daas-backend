<?php

declare(strict_types=1);

namespace Application\Test\Unit\Desktop;

use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\Desktop\Model\Desktop;
use Application\Core\Desktop\Repository\DesktopRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class DesktopRepositoryTest extends TestCase
{
    private EntityManagerInterface $em;
    private DesktopRepository $desktopRepository;

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

        $desktopRepository = $application->container()->get(DesktopRepository::class);
        assert($desktopRepository instanceof DesktopRepository);
        $this->desktopRepository = $desktopRepository;
    }

    public function testStoreAndRestoresDesktop(): void
    {
        $desktop = TestEntityBuilder::buildDesktop();

        $this->em->persist($desktop);

        $this->em->flush();
        $this->em->clear();

        $desktopRestored = $this->desktopRepository->find($desktop->id());

        self::assertInstanceOf(Desktop::class, $desktopRestored);
        self::assertEquals($desktop->id(), $desktopRestored->id());
        self::assertEquals($desktop->description(), $desktopRestored->description());
    }

    public function testCanAddAndRemoveDesktopGroups(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $desktop = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktop);

        $desktop->addGroup($desktopGroup);

        $this->em->flush();
        $this->em->clear();

        $desktopRestored = $this->desktopRepository->find($desktop->id());
        self::assertInstanceOf(Desktop::class, $desktopRestored);
        self::assertCount(1, $desktop->groups());
        self::assertEquals($desktopGroup->id(), $desktop->groups()[0]->id());

        $desktopRestored->removeGroup($desktopRestored->groups()[0]);

        $this->em->flush();
        $this->em->clear();

        $desktopReRestored = $this->desktopRepository->find($desktop->id());
        self::assertInstanceOf(Desktop::class, $desktopReRestored);
        self::assertCount(0, $desktopReRestored->groups());
    }

    public function testCantAddDesktopAlreadyAdded(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $desktop = TestEntityBuilder::buildDesktop();

        $desktop->addGroup($desktopGroup);

        self::expectException(DesktopException::class);
        $desktop->addGroup($desktopGroup);
    }

    public function testCantRemoveDesktopNotInGroup(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $desktop = TestEntityBuilder::buildDesktop();

        self::expectException(DesktopException::class);
        $desktop->removeGroup($desktopGroup);
    }
}
