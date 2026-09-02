<?php

namespace Application\Core\Permissions\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Permissions\Model\Endpoint;

/**
 * @extends InjectableEntityRepository<Endpoint>
 * @template-extends InjectableEntityRepository<Endpoint>
 */
final class EndpointRepository extends InjectableEntityRepository
{
    /**
     * @psalm-param non-empty-string $functionName
     */
    public function findByFunctionName(string $functionName): ?Endpoint
    {
        return $this->findOneBy(['functionName' => $functionName]);
    }
}
