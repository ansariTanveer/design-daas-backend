<?php

namespace Application\Core\Permissions\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\Permissions\Model\Endpoint;
use Application\Core\Permissions\Model\EndpointGroup;
use OpenApi\Attributes as OA;

/**
 * @psalm-import-type httpMethod from Endpoint
 */
#[OA\Schema(schema: "infrastructure_permissions_endpoint")]
final class InfrastructurePermissionsEndpointDTO extends AbstractJsonDTO
{
    /** @psalm-var int<1, 2147483647> */
    #[OA\Property(maximum: 2147483647, minimum: 1)]
    public int $unique_permission_id;

    /** @psalm-var non-empty-string */
    #[OA\Property(format: "string")]
    public string $function_name;

    /** @psalm-var non-empty-string */
    #[OA\Property(format: "string")]
    public string $endpoint_url;

    /** @psalm-var httpMethod */
    #[OA\Property(format: "string")]
    public string $request_method;

    #[OA\Property(format: "string")]
    public string $function_desc;

    // TODO: Format of function_params is unknown
    // public array $function_params;

    public function toEntity(EndpointGroup $endpointGroup): Endpoint
    {
        return new Endpoint(
            $this->unique_permission_id,
            $this->function_name,
            $this->endpoint_url,
            $this->request_method,
            $endpointGroup
        );
    }
}
