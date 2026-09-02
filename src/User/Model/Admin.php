<?php

namespace Application\Core\User\Model;

use Application\Core\User\Enum\UserRole;
use Application\Core\User\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(UserRepository::class)]
class Admin extends BaseUser
{
    public function __construct(
        string $name,
        string $email,
        Password $password,
    ) {
        parent::__construct(
            $name,
            $email,
            $password,
            UserRole::ADMIN,
        );
        parent::enable();
    }
}
