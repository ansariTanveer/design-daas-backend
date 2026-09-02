<?php

declare(strict_types=1);

namespace Application\Core\User\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\User;
use Doctrine\ORM\NonUniqueResultException;
use RuntimeException;

/**
 * @extends InjectableEntityRepository<BaseUser>
 * @template-extends InjectableEntityRepository<BaseUser>
 */
class UserRepository extends InjectableEntityRepository
{
    /**
     * @psalm-param array<positive-int> $ids
     * @return array<User>
     * @throws BaseUserException
     */
    public function findBaseUsers(array $ids): array
    {
        $result = $this->getEntityManager()
        ->createQuery(/** @lang DQL */
            "SELECT bu FROM " . BaseUser::class . " bu WHERE bu.id IN (:ids)"
        )
        ->setParameter('ids', $ids)
        ->getResult();

        assert(is_array($result));

        if (count($result) !== count($ids)) {
            throw BaseUserException::invalidId(null);
        }

        return $result;
    }

    public function findBaseUserById(int $id): ?BaseUser
    {
        try {
            $result = $this->getEntityManager()
                ->createQuery(
                /** @lang DQL */
                    "SELECT bu FROM " . BaseUser::class . " bu WHERE bu.id = :id"
                )
                ->setParameter('id', $id)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        assert(is_null($result) || $result instanceof Admin || $result instanceof User);
        return $result;
    }

    public function findBaseUserByEmail(string $email): null|Admin|User
    {
        try {
            $result = $this->getEntityManager()
                ->createQuery(
                /** @lang DQL */
                    "SELECT bu FROM " . BaseUser::class . " bu WHERE bu.email = :email"
                )
                ->setParameter('email', $email)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        assert(is_null($result) || $result instanceof Admin || $result instanceof User);
        return $result;
    }

    public function findBaseUserByGuid(string $guid): null|Admin|User
    {
        try {
            $result = $this->getEntityManager()
                ->createQuery(
                /** @lang DQL */
                    "SELECT bu FROM " . BaseUser::class . " bu WHERE bu.guid = :guid"
                )
                ->setParameter('guid', $guid)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        assert(is_null($result) || $result instanceof Admin || $result instanceof User);
        return $result;
    }

    public function findUserById(int $id): ?User
    {
        $result = $this->findBaseUserById($id);
        return $result instanceof User ? $result : null;
    }

    public function findAdminById(int $id): ?Admin
    {
        $result = $this->findBaseUserById($id);
        return $result instanceof Admin ? $result : null;
    }

    public function findUserByEmail(string $email): ?User
    {
        $result = $this->findBaseUserByEmail($email);
        return $result instanceof User ? $result : null;
    }

    public function findAdminByEmail(string $email): ?Admin
    {
        $result = $this->findBaseUserByEmail($email);
        return $result instanceof Admin ? $result : null;
    }

    public function findUserByGuid(string $guid): ?User
    {
        $result = $this->findBaseUserByGuid($guid);
        return $result instanceof User ? $result : null;
    }

    public function findAdminByGuid(string $guid): ?Admin
    {
        $result = $this->findBaseUserByGuid($guid);
        return $result instanceof Admin ? $result : null;
    }

    /**
     * @return BaseUser[]
     */
    public function findAllBaseUsers(): array
    {
        /** @var BaseUser[] */
        return $this->findAll();
    }

    /**
     * @return array<Admin>
     */
    public function findAllAdmins(): array
    {
        /** @var array<Admin> */
        return $this->getEntityManager()
            ->createQuery(
            /** @lang DQL */
                "SELECT bu FROM " . BaseUser::class . " bu WHERE bu INSTANCE OF " . Admin::class
            )
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findAllUsers(?int $maxResults = null, ?int $firstResult = null): array
    {
        /** @var array<User> */
        return $this->getEntityManager()
            ->createQuery(
            /** @lang DQL */
                "SELECT bu
                    FROM " . BaseUser::class . " bu
                    WHERE bu INSTANCE OF " . User::class . "
                    ORDER BY bu.id ASC"
            )
            ->setMaxResults($maxResults)
            ->setFirstResult($firstResult)
            ->getResult();
    }

    public function findBaseUserByRegistrationCode(
        string $email,
        string $registrationCode
    ): ?BaseUser {
        try {
            $result = $this->getEntityManager()
                ->createQuery(
                /** @lang DQL */
                    "SELECT bu FROM " . BaseUser::class . " bu WHERE"
                    . " bu.email = :email"
                    . " AND bu.registrationCode = :code"
                    . " AND bu.registrationUsedMoment IS NULL"
                )
                ->setParameter('email', $email)
                ->setParameter('code', $registrationCode)
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            throw new RuntimeException($e->getMessage(), 0, $e);
        }

        assert(is_null($result) || $result instanceof BaseUser);
        return $result;
    }

    public function store(BaseUser $user): void
    {
        $this->getEntityManager()->persist($user);
    }
}
