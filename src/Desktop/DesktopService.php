<?php

declare(strict_types=1);

namespace Application\Core\Desktop;

use Application\Core\Desktop\DTO\DesktopDetailsDTO;
use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\Desktop\Exception\DesktopGroupException;
use Application\Core\Desktop\Model\Desktop;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\Desktop\Repository\DesktopGroupRepository;
use Application\Core\Desktop\Repository\DesktopRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DesktopService
{
    public function __construct(
        private EntityManagerInterface $em,
        private DesktopGroupRepository $desktopGroupRepository,
        private DesktopRepository $desktopRepository
    ) {
    }

    public function addDesktopGroup(DesktopGroupDetailsDTO $desktopGroupDetailsDTO): DesktopGroup
    {
        $desktopGroup = new DesktopGroup(
            $desktopGroupDetailsDTO->description
        );

        $this->em->persist($desktopGroup);
        $this->em->flush();

        return $desktopGroup;
    }

    public function createDesktop(DesktopDetailsDTO $desktopDetailsDTO): Desktop
    {
        $desktop = new Desktop($desktopDetailsDTO->description);

        $this->em->persist($desktop);
        $this->em->flush();

        return $desktop;
    }

    /**
     * @throws DesktopGroupException - Invalid group ID given
     */
    public function getGroup(int $groupId): DesktopGroup
    {
        $group = $this->desktopGroupRepository->find($groupId);
        if (!($group instanceof DesktopGroup)) {
            throw DesktopGroupException::notFound($groupId);
        }

        return $group;
    }

    /**
     * @return array<Desktop>
     */
    public function listDesktop(): array
    {
        return $this->desktopRepository->findAll();
    }

    /**
     * @psalm-param positive-int $desktopId
     * @throws DesktopException - Invalid desktop ID given
     */
    public function getDesktop(int $desktopId): DesktopDetailsDTO
    {
        $desktop = $this->desktopRepository->find($desktopId);
        if (!($desktop instanceof Desktop)) {
            throw DesktopException::notFound($desktopId);
        }

        return DesktopDetailsDTO::fromEntity($desktop);
    }

    /**
     * @psalm-param positive-int $desktopGroupId
     * @throws DesktopGroupException - Invalid desktop group ID given
     */
    public function getDesktopGroupDTO(int $desktopGroupId): DesktopGroupDetailsDTO
    {
        $desktopGroup = $this->desktopGroupRepository->find($desktopGroupId);
        if (!($desktopGroup instanceof DesktopGroup)) {
            throw DesktopGroupException::notFound($desktopGroupId);
        }

        return DesktopGroupDetailsDTO::fromEntity($desktopGroup);
    }
}
