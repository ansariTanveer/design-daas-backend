<?php

declare(strict_types=1);

namespace Application\Test\Unit\Desktop;

use Application\Core\Desktop\DesktopGroupService;
use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class DesktopGroupServiceTest extends TestCase
{
    private EntityManagerInterface $em;
    private DesktopGroupService $sut;

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

        $sut = $application->container()->get(DesktopGroupService::class);
        assert($sut instanceof DesktopGroupService);
        $this->sut = $sut;
    }

    public function testListDesktopGroups(): void
    {
        $desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup);

        $desktopGroup2 = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($desktopGroup2);

        $this->em->flush();
        $this->em->clear();

        $result = $this->sut->listDesktopGroups();
        self::assertCount(2, $result);
        self::assertInstanceOf(DesktopGroupDetailsDTO::class, $result[0]);
        self::assertInstanceOf(DesktopGroupDetailsDTO::class, $result[1]);
    }
}
