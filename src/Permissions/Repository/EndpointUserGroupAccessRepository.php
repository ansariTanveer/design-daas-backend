<?php

namespace Application\Core\Permissions\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Permissions\Model\Endpoint;
use Application\Core\Permissions\Model\EndpointUserGroupAccess;
use Application\Core\User\Model\UserGroup;

/**
 * @extends InjectableEntityRepository<EndpointUserGroupAccess>
 * @template-extends InjectableEntityRepository<EndpointUserGroupAccess>
 */
final class EndpointUserGroupAccessRepository extends InjectableEntityRepository
{
    public function findByUserGroupAndEndpoint(UserGroup $userGroup, Endpoint $endpoint): ?EndpointUserGroupAccess
    {
        return $this->findOneBy(['userGroup' => $userGroup, 'endpoint' => $endpoint]);
    }
}
