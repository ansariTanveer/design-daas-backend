<?php

namespace Application\Core\User\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\User\Model\Admin;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "admin_details")]
class AdminDetailsSwaggerDTO extends AbstractJsonDTO
{
    #[OA\Property(description: "The admin's id")]
    public int $id;

    #[OA\Property(description: "The admin's name", example: "Max Mustermann")]
    public string $name;

    #[OA\Property(description: "The admin's email address", example: "max.mustermann@example.com")]
    public string $email;
    #[OA\Property(description: "The user role", type: "string")]
    public string $role;

    public static function fromEntity(Admin $admin): self
    {
        $dto = new self();

        $dto->id = $admin->id();
        $dto->name = $admin->name();
        $dto->email = $admin->email();
        $dto->role = 'admin';

        return $dto;
    }

    /**
     * @param array<Admin> $admins
     * @return array<self>
     */
    public static function fromAdmins(array $admins): array
    {
        $dtos = [];
        foreach ($admins as $admin) {
            $dtos[] = self::fromEntity($admin);
        }

        return $dtos;
    }
}
