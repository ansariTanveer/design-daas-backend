<?php

namespace Application\Core\User\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "user_group_creation", required: ["description"])]
class UserGroupCreationDTO extends AbstractJsonDTO
{
    #[OA\Property(description: "The User Group's description", example: "Lorem ipsum dolor sit amet")]
    public string $description;
}
