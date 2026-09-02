<?php

declare(strict_types=1);

namespace Application\Test\Unit\User;

use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Exception\InvalidPasswordException;
use Application\Core\User\Exception\UserServiceException;
use Application\Core\User\Model\Password;
use Application\Core\User\Model\User;
use Application\Core\User\Model\UserGroup;
use Application\Core\User\Repository\UserRepository;
use Application\Core\User\Service\UserService;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class UserServiceUpdateUserTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserService $userService;
    private User $user;
    private UserGroup $groupToRemain;
    private UserGroup $groupToRemove;
    private UserGroup $groupToAdd;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws BaseUserException
     */
    protected function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($application);

        $this->em = TestApplicationFactory::extractEntityManager($application);

        $userRepository = $application->container()->get(UserRepository::class);
        assert($userRepository instanceof UserRepository);
        $this->userRepository = $userRepository;

        $userService = $application->container()->get(UserService::class);
        assert($userService instanceof UserService);
        $this->userService = $userService;

        $this->groupToRemain = TestEntityBuilder::buildUserGroup();
        $this->em->persist($this->groupToRemain);

        $this->groupToRemove = TestEntityBuilder::buildUserGroup();
        $this->em->persist($this->groupToRemove);

        $this->groupToAdd = TestEntityBuilder::buildUserGroup();
        $this->em->persist($this->groupToAdd);

        $this->user = TestEntityBuilder::buildUser(
            ['password' => Password::fromPlainString('!12345BaBaBlackSheep!')]
        );
        $this->user->addGroup($this->groupToRemain);
        $this->user->addGroup($this->groupToAdd);
        $this->em->persist($this->user);

        $this->em->flush();
        $this->em->clear();
    }

    public function testRejectsInvalidEmailAddress(): void
    {
        self::expectException(UserServiceException::class);

        $dto = new UserDetailsSwaggerDTO();
        $dto->email = 'test#example.com';
        $dto->password = "!SuperSecretPassword";

        $this->userService->updateUser($this->user->id(), $dto);
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public function testRejectsInvalidPassword(): void
    {
        self::expectException(InvalidPasswordException::class);

        $dto = new UserDetailsSwaggerDTO();
        $dto->email = 'test@example.com';
        $dto->password = "123";

        $this->userService->updateUser($this->user->id(), $dto);
    }

    public function testUpdatesUser(): void
    {
        $dto = new UserDetailsSwaggerDTO();
        $dto->name = 'Joe Normal';
        $dto->email = 'joe.normal@example.com';
        $dto->password = "!SuperSecretPassword";
        $dto->groups = [$this->groupToRemain->id(), $this->groupToAdd->id()];

        $this->userService->updateUser($this->user->id(), $dto);

        $this->em->clear();

        $userReloaded = $this->userRepository->findUserById($this->user->id());
        self::assertInstanceOf(User::class, $userReloaded);
        self::assertEquals('Joe Normal', $userReloaded->name());
        self::assertEquals('joe.normal@example.com', $userReloaded->email());
        self::assertTrue($userReloaded->password()->equals(Password::fromPlainString($dto->password)));

        self::assertCount(2, $userReloaded->groups());
        self::assertTrue($userReloaded->hasGroup($this->groupToAdd));
        self::assertTrue($userReloaded->hasGroup($this->groupToRemain));
        self::assertFalse($userReloaded->hasGroup($this->groupToRemove));
    }

    public function testPreservesValuesOnNoChange(): void
    {
        $dto = new UserDetailsSwaggerDTO();

        $this->userService->updateUser($this->user->id(), $dto);

        $this->em->clear();

        $userReloaded = $this->userRepository->findUserById($this->user->id());
        self::assertInstanceOf(User::class, $userReloaded);
        self::assertEquals($this->user->name(), $userReloaded->name());
        self::assertEquals($this->user->email(), $userReloaded->email());
    }
}
