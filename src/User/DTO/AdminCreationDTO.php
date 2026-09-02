<?php

namespace Application\Core\User\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "admin_creation", required: ["name", "email", "password"])]
class AdminCreationDTO extends AbstractJsonDTO
{
    #[OA\Property(description: "The Admin's name", example: "Max Mustermann")]
    public string $name;

    #[OA\Property(description: "The Admin's email address", example: "max.mustermann@example.com")]
    public string $email;

    #[OA\Property(description: "The Admin's password", writeOnly: true, example: "swordfish")]
    public string $password;
}
