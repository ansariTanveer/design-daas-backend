<?php

declare(strict_types=1);

namespace Application\Core\Desktop;

use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Core\Desktop\Repository\DesktopGroupRepository;

final readonly class DesktopGroupService
{
    public function __construct(
        private DesktopGroupRepository $desktopGroupRepository,
    ) {
    }

    /**
     * @return array<DesktopGroupDetailsDTO>
     */
    public function listDesktopGroups(): array
    {
        $allDesktopGroups = $this->desktopGroupRepository->findAll();

        return DesktopGroupDetailsDTO::fromEntities($allDesktopGroups);
    }
}
