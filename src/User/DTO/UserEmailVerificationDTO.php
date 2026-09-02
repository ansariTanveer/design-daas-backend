<?php

declare(strict_types=1);

namespace Application\Core\User\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\User\Model\BaseUser;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "user_email_verification")]
class UserEmailVerificationDTO extends AbstractJsonDTO
{
    #[OA\Property(
        description: "Email to verify",
        minLength: 5,
        writeOnly: true,
    )]
    public string $email;

    /** @psalm-var non-empty-string */
    #[OA\Property(
        description: "Registration code",
        minLength: BaseUser::REGISTRATION_CODE_LENGTH,
        writeOnly: true,
    )]
    public string $registration_code;
}
