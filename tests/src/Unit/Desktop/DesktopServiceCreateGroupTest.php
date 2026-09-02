<?php

declare(strict_types=1);

namespace Application\Test\Unit\Desktop;

use Application\Core\Desktop\DesktopService;
use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\Desktop\Repository\DesktopGroupRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class DesktopServiceCreateGroupTest extends TestCase
{
    private EntityManagerInterface $em;
    private DesktopGroupRepository $desktopGroupRepository;

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

        $desktopGroupRepository = $application->container()->get(DesktopGroupRepository::class);
        assert($desktopGroupRepository instanceof DesktopGroupRepository);
        $this->desktopGroupRepository = $desktopGroupRepository;

        $desktopService = $application->container()->get(DesktopService::class);
        assert($desktopService instanceof DesktopService);
        $this->desktopService = $desktopService;
    }

    public function testCreatesDesktopGroup(): void
    {
        $requestDTO = new DesktopGroupDetailsDTO();
        $requestDTO->description = 'Potato tomato';

        $this->desktopService->addDesktopGroup($requestDTO);

        $this->em->clear();

        $desktopGroupsRestored = $this->desktopGroupRepository->findAll();
        self::assertCount(1, $desktopGroupsRestored);

        $desktopGroupRestored = $desktopGroupsRestored[0];
        self::assertInstanceOf(DesktopGroup::class, $desktopGroupRestored);

        self::assertEquals('Potato tomato', $desktopGroupRestored->description());
    }
}
