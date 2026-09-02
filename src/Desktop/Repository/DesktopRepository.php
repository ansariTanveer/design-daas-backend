<?php

namespace Application\Core\Desktop\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Desktop\Model\Desktop;
use Doctrine\ORM\EntityNotFoundException;

/**
 * @extends InjectableEntityRepository<Desktop>
 * @template-extends InjectableEntityRepository<Desktop>
 */
final class DesktopRepository extends InjectableEntityRepository
{
    public function getById(int $id): Desktop
    {
        $desktop = $this->find($id);
        if (!$desktop instanceof Desktop) {
            throw new EntityNotFoundException('Desktop id not found: ' . $id);
        }

        return $desktop;
    }
}
