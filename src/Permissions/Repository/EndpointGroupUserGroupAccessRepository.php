<?php

namespace Application\Core\Permissions\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Permissions\Model\EndpointGroup;
use Application\Core\Permissions\Model\EndpointGroupUserGroupAccess;
use Application\Core\User\Model\UserGroup;

/**
 * @extends InjectableEntityRepository<EndpointGroupUserGroupAccess>
 * @template-extends InjectableEntityRepository<EndpointGroupUserGroupAccess>
 */
final class EndpointGroupUserGroupAccessRepository extends InjectableEntityRepository
{
    public function findByUserGroupAndEndpointGroup(
        UserGroup $userGroup,
        EndpointGroup $endpointGroup
    ): ?EndpointGroupUserGroupAccess {
        return $this->findOneBy(['userGroup' => $userGroup, 'endpointGroup' => $endpointGroup]);
    }
}
