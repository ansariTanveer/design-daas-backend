<?php

namespace Application\Core\User\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\User\Model\BaseUser;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Schema(schema: "user_details")]
class UserDetailsSwaggerDTO extends AbstractJsonDTO
{
    #[OA\Property(description: "The user's ID", type: "int", minimum: 1, readOnly: true)]
    public int $id;

    #[OA\Property(description: "The user's name", example: "Max Mustermann")]
    public string $name;

    #[OA\Property(description: "The user's email address", example: "max.mustermann@example.com")]
    public string $email;

    #[OA\Property(description: "The user role", type: "string")]
    public string $role;

    /** @var array<positive-int> */
    #[OA\Property(
        description: "List of groups to attach to this user. Replaces existing list if given.",
        items: new OA\Items(type: 'integer', minimum: 1),
        example: [10, 11, 12]
    )]
    public array $groups;

    #[OA\Property(readOnly: true)]
    public bool $enabled;

    /** @psalm-var non-empty-string */
    #[OA\Property(
        description: "The user's password (min. 8 letters, must be mixed case and contain special characters",
        minLength: 8,
        writeOnly: true,
        example: "!myPassword123!"
    )]
    public string $password;

    /**
     * @param BaseUser $user
     * @return self
     */
    public static function fromUser(BaseUser $user): self
    {
        $dto = new self();

        $dto->id = $user->id();
        $dto->name = $user->name();
        $dto->email = $user->email();
        $dto->enabled = $user->enabled();
        $dto->role = 'user';

        $dto->groups = array_map(
            function ($g): int {
                return $g->id();
            },
            $user->groups()
        );

        return $dto;
    }

    /**
     * @param array<BaseUser> $users
     * @return array<self>
     */
    public static function fromUsers(array $users): array
    {
        $result = [];
        foreach ($users as $user) {
            $result[] = self::fromUser($user);
        }

        return $result;
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
