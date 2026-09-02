<?php

namespace Application\Core\Permissions\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "permissions_request", required: ['token', 'function_name'])]
final class PermissionRequestDTO extends AbstractJsonDTO
{
    /** @var non-empty-string */
    #[OA\Property(format: "string")]
    public string $token;

    /** @var non-empty-string */
    #[OA\Property(format: "string")]
    public string $function_name;
}
