<?php

namespace Application\Core\User\Model;

use Application\Core\User\Enum\UserRole;
use Application\Core\User\Repository\UserRepository;
use BjoernGoetschke\DateTime\Moment;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(UserRepository::class)]
class User extends BaseUser
{
    public function __construct(
        string $name,
        string $email,
        Password $password,
        Moment $registrationTimeout = null,
    ) {
        parent::__construct(
            $name,
            $email,
            $password,
            UserRole::USER,
        );
        $this->setNewRegistrationCode($registrationTimeout);
    }

    /**
     * @psalm-return non-empty-string
     */
    public function registrationCode(): string
    {
        assert(!is_null(parent::registrationCode()));

        return parent::registrationCode();
    }
}
