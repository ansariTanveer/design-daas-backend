<?php

namespace Application\Test\Integration\Desktop;

use Application\Common\Application\Http\HttpApplication;
use Application\Core\Desktop\Model\Desktop;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\Desktop\Repository\DesktopGroupRepository;
use Application\Core\Desktop\Repository\DesktopRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class DesktopDesktopGroupTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    private DesktopRepository $desktopRepository;
    private DesktopGroupRepository $desktopGroupRepository;
    private DesktopGroup $desktopGroup1;
    private Desktop $desktop1;
    private Desktop $desktop2;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $desktopGroupRepository = $this->application->container()->get(DesktopGroupRepository::class);
        assert($desktopGroupRepository instanceof DesktopGroupRepository);
        $this->desktopGroupRepository = $desktopGroupRepository;
        $desktopRepository = $this->application->container()->get(DesktopRepository::class);
        assert($desktopRepository instanceof DesktopRepository);
        $this->desktopRepository = $desktopRepository;

        $this->desktopGroup1 = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($this->desktopGroup1);
        $this->desktop1 = TestEntityBuilder::buildDesktop();
        $this->em->persist($this->desktop1);

        $this->desktop2 = TestEntityBuilder::buildDesktop();
        $this->em->persist($this->desktop2);

        $this->em->flush();
        $this->em->clear();
    }

    public function testCreateDesktopGroup(): void
    {
        $this->desktop1 = $this->desktopRepository->getById($this->desktop1->id());
        $this->desktop2 = $this->desktopRepository->getById($this->desktop2->id());
        $this->desktopGroup1 = $this->desktopGroupRepository->getById($this->desktopGroup1->id());

        $this->desktop1->addGroup($this->desktopGroup1);
        $this->desktop2->addGroup($this->desktopGroup1);

        $this->em->flush();
        $this->em->clear();

        $desktopReloaded = $this->desktopRepository->getById($this->desktop1->id());
        self::assertCount(1, $desktopReloaded->groups());

        $desktopReloaded = $this->desktopRepository->getById($this->desktop2->id());
        self::assertCount(1, $desktopReloaded->groups());

        $desktopGroupReloaded = $this->desktopGroupRepository->getById($this->desktopGroup1->id());
        self::assertCount(2, $desktopGroupReloaded->desktops());
    }
}
