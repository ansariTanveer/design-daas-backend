<?php

declare(strict_types=1);

namespace Application\Core\User\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\User\Exception\UserGroupException;
use Application\Core\User\Model\UserGroup;

/**
 * @extends InjectableEntityRepository<UserGroup>
 * @template-extends InjectableEntityRepository<UserGroup>
 */
class UserGroupRepository extends InjectableEntityRepository
{
    /**
     * @psalm-param array<positive-int> $ids
     * @return array<UserGroup>
     * @throws UserGroupException
     */
    public function findGroups(array $ids): array
    {
        $result = $this->getEntityManager()
            ->createQuery(/** @lang DQL */
                "SELECT ug FROM " . UserGroup::class . " ug WHERE ug.id IN (:ids)"
            )
            ->setParameter('ids', $ids)
            ->getResult();

        assert(is_array($result));

        if (count($result) !== count($ids)) {
            throw UserGroupException::userGroupNotFound();
        }

        return $result;
    }

    public function store(UserGroup $group): void
    {
        $this->getEntityManager()->persist($group);
    }
}
