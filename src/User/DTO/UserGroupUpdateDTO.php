<?php

namespace Application\Core\User\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "user_group_update",)]
class UserGroupUpdateDTO extends AbstractJsonDTO
{
    /** @psalm-var array<positive-int> */
    #[OA\Property(
        description: "List of users to attach to this group. Supplants existing list if given.",
        items: new OA\Items(type: 'integer', minimum: 1, minLength: 1)
    )]
    public array $userIds;

    #[OA\Property]
    public string $description;
}
