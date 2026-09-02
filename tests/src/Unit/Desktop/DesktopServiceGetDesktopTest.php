<?php

declare(strict_types=1);

namespace Application\Test\Unit\Desktop;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Desktop\DesktopService;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\Desktop\Repository\DesktopRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class DesktopServiceGetDesktopTest extends TestCase
{
    private ApplicationInterface $application;
    private DesktopService $sut;
    private EntityManagerInterface $em;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $sut = $this->application->container()->get(DesktopService::class);
        assert($sut instanceof DesktopService);
        $this->sut = $sut;

        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @throws DesktopException
     */
    public function testDesktopGetDetailsReturnsUserDetails(): void
    {
        $desktop = TestEntityBuilder::buildDesktop();
        $this->em->persist($desktop);
        $this->em->flush();

        $id = $desktop->id();
        assert($id >= 1);
        $resultObject = $this->sut->getDesktop($id);

        self::assertEquals($desktop->id(), $resultObject->id);
        self::assertEquals($desktop->description(), $resultObject->description);
    }

    /**
     * @throws DesktopException
     */
    public function testUserGetDetailsFailsOnInvalidId(): void
    {
        self::expectException(DesktopException::class);
        $this->sut->getDesktop(12837);
    }
}
