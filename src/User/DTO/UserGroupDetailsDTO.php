<?php

namespace Application\Core\User\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\User\Model\UserGroup;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Schema(schema: "user_group_details")]
class UserGroupDetailsDTO extends AbstractJsonDTO
{
    #[OA\Property(description: "The User Group's ID", minimum: 1, readOnly: true)]
    public int $id;

    #[OA\Property(description: "The User Group's description", example: "Lorem ipsum dolor sit amet")]
    public string $description;

    /**
     * @param UserGroup $group
     * @return self
     */
    public static function fromGroup(UserGroup $group): self
    {
        $dto = new self();

        $dto->id = $group->id();
        $dto->description = $group->description();

        return $dto;
    }

    /**
     * @param UserGroup[] $groups
     * @return self[]
     */
    public static function fromGroups(array $groups): array
    {
        return array_map(
            function ($g): self {
                return self::fromGroup($g);
            },
            $groups
        );
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
