<?php

namespace Application\Core\Permissions\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\Model\BaseUser;
use OpenApi\Attributes as OA;
use stdClass;

/**
 * @psalm-import-type AccessEnumValue from AccessEnum
 */
#[OA\Schema(schema: "permissions_result")]
final class PermissionResultDTO extends AbstractJsonDTO
{
    /** @psalm-var AccessEnumValue */
    #[OA\Property(format: "string", enum: AccessEnum::class)]
    public string $result;

    /** @psalm-var UserDetailsSwaggerDTO */
    #[OA\Property]
    public UserDetailsSwaggerDTO $user;

    protected static function convertValueFromJson(string $name, $value)
    {
        switch ($name) {
            case "user":
                assert($value instanceof stdClass);
                return UserDetailsSwaggerDTO::fromJson($value);
            default:
                return $value;
        }
    }

    public static function fromUserAndAccessEnum(BaseUser $user, AccessEnum $accessEnum): self
    {
        $dto = new self();
        $dto->user = UserDetailsSwaggerDTO::fromUser($user);
        $dto->result = $accessEnum->value;

        return $dto;
    }
}
