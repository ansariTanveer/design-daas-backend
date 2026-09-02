<?php

namespace Application\Core\OAuth2\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\User\Model\BaseUser;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "user_session_details")]
class UserSessionDetailsSwaggerDTO extends AbstractJsonDTO
{
    #[OA\Property(minimum: 1)]
    public int $user_id;

    #[OA\Property(format: "email", maxLength: 255)]
    public string $email;

    #[OA\Property(maxLength: 255)]
    public string $name;

    /** @var string[] */
    #[OA\Property]
    public array $scopes;

    /**
     * @param string[] $scopes
     */
    public static function fromUserAndScopes(BaseUser $user, array $scopes): UserSessionDetailsSwaggerDTO
    {
        $dto = new self();

        $dto->user_id = $user->id();
        $dto->email = $user->email();
        $dto->name = $user->name();

        $dto->scopes = $scopes;

        return $dto;
    }
}
