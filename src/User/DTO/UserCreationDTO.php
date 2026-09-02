<?php

namespace Application\Core\User\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "user_creation", required: ["name", "email"])]
class UserCreationDTO extends AbstractJsonDTO
{
    #[OA\Property(description: "The User's name", example: "Max Mustermann")]
    public string $name;

    #[OA\Property(description: "The User's email address", example: "max.mustermann@example.com")]
    public string $email;

    /** @psalm-var non-empty-string */
    #[OA\Property(
        description: "The user's password (min. 8 letters, must be mixed case and contain special characters",
        minLength: 8,
        example: "!myPassword123!"
    )]
    public string $password;
}
