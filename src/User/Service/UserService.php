<?php

declare(strict_types=1);

namespace Application\Core\User\Service;

use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\Desktop\Model\Desktop;
use Application\Core\Desktop\Repository\DesktopRepository;
use Application\Core\User\DTO\AdminCreationDTO;
use Application\Core\User\DTO\AdminDetailsSwaggerDTO;
use Application\Core\User\DTO\UserCreationDTO;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\DTO\UserEmailVerificationDTO;
use Application\Core\User\Enum\UserGetDetailsResult;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Exception\InvalidPasswordException;
use Application\Core\User\Exception\UserGroupException;
use Application\Core\User\Exception\UserServiceException;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\Password;
use Application\Core\User\Model\User;
use Application\Core\User\Repository\UserGroupRepository;
use Application\Core\User\Repository\UserRepository;
use Application\Core\Util\Mail\MailLanguageEnum;
use Application\Core\Util\Mail\MailService;
use BjoernGoetschke\DateTime\Moment;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private DesktopRepository $desktopRepository,
        private UserGroupRepository $userGroupRepository,
        private EntityManagerInterface $em,
        private MailService $mailService,
    ) {
    }

    /**
     * @throws UserServiceException
     */
    public function registerAdminFromDto(AdminCreationDTO $dto): Admin
    {
        return $this->registerAdmin(
            $dto->name,
            $dto->email,
            $dto->password,
        );
    }

    /**
     * @throws UserServiceException
     */
    public function registerAdmin(
        string $username,
        string $email,
        string $password,
    ): Admin {
        $password = $this->validateBaseUser($email, $password);
        $admin = new Admin($username, $email, $password);

        $this->em->persist($admin);
        $this->em->flush();

        return $admin;
    }

    /**
     * @throws UserServiceException
     * @throws InvalidPasswordException
     */
    public function adminGetDetails(int $id): AdminDetailsSwaggerDTO
    {
        $admin = $this->userRepository->findAdminById($id);

        if (!$admin instanceof Admin) {
            throw UserServiceException::invalidAdminId();
        }

        return AdminDetailsSwaggerDTO::fromEntity($admin);
    }

    public function registerUser(UserCreationDTO $dto): User
    {
        $password = $this->validateBaseUser($dto->email, $dto->password);
        $user = new User($dto->name, $dto->email, $password);

        $this->userRepository->store($user);

        $this->mailService->enqueueValidateEmail(
            ['registration_code' => $user->registrationCode()],
            $user->email(),
        );

        return $user;
    }

    /**
     * @throws UserServiceException
     * @throws InvalidPasswordException
     * @throws BaseUserException
     * @throws UserGroupException
     */
    public function updateUser(int $userId, UserDetailsSwaggerDTO $dto): User
    {
        $user = $this->userRepository->findUserById($userId);
        if (!$user instanceof User) {
            throw UserServiceException::invalidUserId();
        }

        if (isset($dto->name)) {
            $user->setName($dto->name);
        }

        if (isset($dto->email)) {
            if (filter_var($dto->email, FILTER_VALIDATE_EMAIL) === false) {
                throw UserServiceException::invalidEmailAddress();
            }

            $user->setEmail($dto->email);
        }

        if ($dto->hasProperty('groups')) {
            $groupsOfUserNow = $this->userGroupRepository->findGroups($dto->groups);

            foreach ($user->groups() as $groupOfUserCurrently) {
                if (!in_array($groupOfUserCurrently->id(), $dto->groups, true)) {
                    $user->removeGroup($groupOfUserCurrently);
                }
            }

            foreach ($groupsOfUserNow as $groupUfUserNow) {
                if (!$user->hasGroup($groupUfUserNow)) {
                    $user->addGroup($groupUfUserNow);
                }
            }
        }

        if (isset($dto->password)) {
            $user->setPassword(Password::fromPlainString($dto->password));
        }

        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    /**
     * @throws UserServiceException
     */
    private function validateBaseUser(
        string $email,
        string | Password $password,
    ): Password {
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            throw UserServiceException::invalidEmailAddress();
        }

        if (!is_null($this->userRepository->findBaseUserByEmail($email))) {
            throw UserServiceException::emailAddressInUse();
        }

        // cover case where the password is generated outside of this function
        if ($password instanceof Password) {
            return $password;
        }

        try {
            return Password::fromPlainString($password);
        } catch (InvalidPasswordException $e) {
            throw UserServiceException::invalidPassword($e);
        }
    }

    /**
     * @throws UserServiceException
     */
    public function enableUser(int $userId): User
    {
        $user = $this->userRepository->findUserById($userId);

        if (!$user instanceof User) {
            throw UserServiceException::invalidUserId();
        }

        $user->enable();
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @throws UserServiceException
     */
    public function disableUser(int $userId): User
    {
        $user = $this->userRepository->findUserById($userId);

        if (!$user instanceof User) {
            throw UserServiceException::invalidUserId();
        }

        $user->disable();
        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /**
     * @return array<UserDetailsSwaggerDTO>
     */
    public function userList(): array
    {
        return UserDetailsSwaggerDTO::fromUsers(
            $this->userRepository->findAllUsers(),
        );
    }

    /**
     * @return array<UserDetailsSwaggerDTO>
     */
    public function userListPage(int $pageLength, int $page): array
    {
        $offset = $page * $pageLength;
        return UserDetailsSwaggerDTO::fromUsers(
            $this->userRepository->findAllUsers($pageLength, $offset),
        );
    }

    /**
     * @param int $userId
     * @return object{result: UserGetDetailsResult, 'userDetailsDTO': null|UserDetailsSwaggerDTO|AdminDetailsSwaggerDTO}
     */
    public function userGetDetails(int $userId): object
    {
        $dto = (object)[
            'result' => UserGetDetailsResult::USER_NOT_FOUND,
            'userDetailsDTO' => null,
        ];

        $user = $this->userRepository->findBaseUserById($userId);
        if (is_null($user)) {
            return $dto;
        }

        $dto = match (get_class($user)) {
            Admin::class => AdminDetailsSwaggerDTO::fromEntity($user),
            User::class => UserDetailsSwaggerDTO::fromUser($user),
            default => null,
        };

        return (object)[
            'result' => UserGetDetailsResult::OK,
            'userDetailsDTO' => $dto,
        ];
    }

    public function hasAccessToDesktop(BaseUser $user, int $desktopId): bool
    {
        if ($user instanceof Admin) {
            return true;
        }

        $desktop = $this->desktopRepository->find($desktopId);
        if (!($desktop instanceof Desktop)) {
            throw DesktopException::notFound($desktopId);
        }

        foreach ($user->groups() as $userGroup) {
            foreach ($desktop->groups() as $desktopGroup) {
                if ($userGroup->id() === $desktopGroup->id()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @throws UserServiceException
     */
    public function validateEmailRegistrationCode(UserEmailVerificationDTO $dto): void
    {
        $user = $this->userRepository->findBaseUserByRegistrationCode(
            $dto->email,
            $dto->registration_code
        );

        if ($user instanceof User && !$user->enabled() && !$user->registrationCodeExpired()) {
            $user->setRegistrationUsedMoment(Moment::now());
            $user->enable();
            $this->em->persist($user);
            $this->em->flush();
            return;
        }

        throw UserServiceException::invalidRegistrationCode();
    }

    public function deleteAdmin(int $adminId): void
    {
        $user = $this->userRepository->findBaseUserById($adminId);
        if (!$user instanceof Admin) {
            throw UserServiceException::invalidAdminId();
        }

        $this->em->remove($user);
        $this->em->flush();
    }

    /**
     * @param positive-int $userId
     */
    public function deleteUser(int $userId): void
    {
        $user = $this->userRepository->findBaseUserById($userId);
        if (!$user instanceof User) {
            throw UserServiceException::invalidUserId();
        }

        $this->em->remove($user);
        $this->em->flush();
    }

    /**
     * @param non-empty-string $application
     */
    public function requestApplication(
        BaseUser $user,
        string $application,
        MailLanguageEnum $mailLanguageEnum
    ): void {
        $userName = $user->name();
        assert(strlen($userName) > 0);

        $this->mailService->enqueueApplicationRequestEmail(
            [
                'user_name' => $userName,
                'user_id' => '' . $user->id(),
                'application' => $application
            ],
            $user->email(),
            $mailLanguageEnum
        );
    }
}
