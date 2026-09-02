<?php

namespace Application\Core\Permissions\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Permissions\Model\EndpointGroup;
use Application\Core\Permissions\Model\EndpointGroupUserAccess;
use Application\Core\User\Model\BaseUser;

/**
 * @extends InjectableEntityRepository<EndpointGroupUserAccess>
 * @template-extends InjectableEntityRepository<EndpointGroupUserAccess>
 */
final class EndpointGroupUserAccessRepository extends InjectableEntityRepository
{
    public function findByUserAndEndpointGroup(BaseUser $user, EndpointGroup $endpointGroup): ?EndpointGroupUserAccess
    {
        return $this->findOneBy(['user' => $user, 'endpointGroup' => $endpointGroup]);
    }
}
