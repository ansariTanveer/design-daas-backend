<?php

namespace Application\Core\Permissions\DTO;

use Application\Common\JsonDTO\AbstractJsonDTO;
use Application\Core\Permissions\Model\EndpointGroup;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: "infrastructure_permissions")]
final class InfrastructurePermissionsDTO extends AbstractJsonDTO
{
    /** @psalm-var non-empty-string  */
    #[OA\Property(format: "string")]
    public string $unique_group_name;

    #[OA\Property(format: "string")]
    public string $group_description;

    /** @psalm-var positive-int */
    #[OA\Property(minimum: 1)]
    public int $permission_id_from;

    /** @psalm-var positive-int */
    #[OA\Property(minimum: 1)]
    public int $permission_id_to;

    #[OA\Property(format: "string")]
    public string $endpoint_file;

    /** @var InfrastructurePermissionsEndpointDTO[] */
    #[OA\Property(type: 'array', items: new OA\Items(ref: "#/components/schemas/infrastructure_permissions_endpoint"))]
    public array $endpoints;

    protected static function convertValueFromJson(string $name, $value): mixed
    {
        switch ($name) {
            case 'endpoints':
                $endpoints = [];
                assert(is_array($value));
                foreach ($value as $endpointJson) {
                    $endpoints[] = InfrastructurePermissionsEndpointDTO::fromJson($endpointJson);
                }

                return $endpoints;
            default:
                return parent::convertValueFromJson($name, $value);
        }
    }

    public function toEntity(): EndpointGroup
    {
        return new EndpointGroup($this->unique_group_name);
    }
}
