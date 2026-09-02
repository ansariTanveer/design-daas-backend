<?php

namespace Application\Core\Permissions\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Permissions\Model\Endpoint;
use Application\Core\Permissions\Model\EndpointUserAccess;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\User;

/**
 * @extends InjectableEntityRepository<EndpointUserAccess>
 * @template-extends InjectableEntityRepository<EndpointUserAccess>
 */
final class EndpointUserAccessRepository extends InjectableEntityRepository
{
    public function findByUserAndEndpoint(BaseUser $user, Endpoint $endpoint): ?EndpointUserAccess
    {
        return $this->findOneBy(['user' => $user, 'endpoint' => $endpoint]);
    }
}
