<?php

namespace Application\Core\Desktop\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Desktop\Model\DesktopGroup;
use Doctrine\ORM\EntityNotFoundException;

/**
 * @extends InjectableEntityRepository<DesktopGroup>
 * @template-extends InjectableEntityRepository<DesktopGroup>
 */
final class DesktopGroupRepository extends InjectableEntityRepository
{
    public function getById(int $id): DesktopGroup
    {
        $desktop = $this->find($id);
        if (!$desktop instanceof DesktopGroup) {
            throw new EntityNotFoundException('DesktopGroup id not found: ' . $id);
        }

        return $desktop;
    }
}
