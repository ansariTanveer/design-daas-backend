<?php

namespace Application\Core\Desktop\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\Desktop\Model\Desktop;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Schema(schema: "desktop_details")]
class DesktopDetailsDTO extends AbstractJsonDTO
{
    #[OA\Property(description: "The Desktop's ID", minimum: 1, readOnly: true)]
    public int $id;

    #[OA\Property(description: "The Desktop's description", example: "Lorem ipsum dolor sit amet")]
    public string $description;

    /**
     * @var int[]
     */
    #[OA\Property(
        description: "The group ids that this desktop belongs to",
        type: "array",
        items: new OA\Items(type: "integer")
    )]
    public array $groups;

    public static function fromEntity(Desktop $desktop): self
    {
        $dto = new self();
        $dto->id = $desktop->id();
        $dto->description = $desktop->description();

        foreach ($desktop->groups() as $group) {
            $dto->groups[] = $group->id();
        }

        return $dto;
    }

    /**
     * @param array<Desktop> $entities
     * @return array<self>
     */
    public static function fromEntities(array $entities): array
    {
        /** @var array<self> $DTOs */
        $DTOs = [];

        foreach ($entities as $entity) {
            $DTOs[] = self::fromEntity($entity);
        }

        return $DTOs;
    }

    /** @return self[] */
    public static function fromArrayResponse(ResponseInterface $response): array
    {
        $json = json_decode((string)$response->getBody());
        assert(is_array($json));

        /** @var self[] $dtos */
        $dtos = [];

        foreach ($json as $g) {
            $dtos[] = self::fromJson($g);
        }

        return $dtos;
    }
}
