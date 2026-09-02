<?php

namespace Application\Core\Permissions\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Permissions\Model\EndpointGroup;

/**
 * @extends InjectableEntityRepository<EndpointGroup>
 * @template-extends InjectableEntityRepository<EndpointGroup>
 */
final class EndpointGroupRepository extends InjectableEntityRepository
{
    public function findByUniqueEndpointGroupName(string $endpointName): ?EndpointGroup
    {
        return self::findOneBy(['uniqueGroupName' => $endpointName]);
    }
}
