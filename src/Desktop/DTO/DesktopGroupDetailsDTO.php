<?php

namespace Application\Core\Desktop\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\Desktop\Model\DesktopGroup;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "desktop_group_details")]
class DesktopGroupDetailsDTO extends AbstractJsonDTO
{
    #[OA\Property(description: "The Desktop Group's ID", minimum: 1, readOnly: true)]
    public int $id;

    #[OA\Property(description: "The Desktop Group's description", example: "Lorem ipsum dolor sit amet")]
    public string $description;

    /** @psalm-var array<positive-int> */
    #[OA\Property(
        type: 'array',
        items: new OA\Items(type: 'integer', minimum: 1),
        readOnly: true
    )]
    public array $desktopIds;

    public static function fromEntity(DesktopGroup $desktopGroup): self
    {
        $dto = new self();
        $dto->id = $desktopGroup->id();
        $dto->description = $desktopGroup->description();
        $dto->desktopIds = $desktopGroup->desktopIds();

        return $dto;
    }

    /**
     * @param array<DesktopGroup> $desktopGroups
     * @return array<self>
     */
    public static function fromEntities(array $desktopGroups): array
    {
        $DTOs = [];
        foreach ($desktopGroups as $desktopGroup) {
            $DTOs[] = self::fromEntity($desktopGroup);
        }

        return $DTOs;
    }
}
