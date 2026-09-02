<?php

declare(strict_types=1);

namespace Application\Test\Unit\Desktop;

use Application\Core\Desktop\DesktopService;
use Application\Core\Desktop\DTO\DesktopDetailsDTO;
use Application\Core\Desktop\Model\Desktop;
use Application\Core\Desktop\Repository\DesktopRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class DesktopServiceCreateDesktopTest extends TestCase
{
    private EntityManagerInterface $em;
    private DesktopRepository $desktopRepository;

    private DesktopService $desktopService;

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

        $desktopService = $application->container()->get(DesktopService::class);
        assert($desktopService instanceof DesktopService);
        $this->desktopService = $desktopService;
    }

    public function testCreatesDesktopGroup(): void
    {
        $requestDTO = new DesktopDetailsDTO();
        $requestDTO->description = "Lorem ipsum dolor sit amet";
        $this->desktopService->createDesktop($requestDTO);

        $this->em->clear();

        $desktopsRestored = $this->desktopRepository->findAll();
        self::assertCount(1, $desktopsRestored);

        $desktopRestored = $desktopsRestored[0];
        self::assertInstanceOf(Desktop::class, $desktopRestored);

        self::assertEquals($requestDTO->description, $desktopRestored->description());
    }
}
