<?php

declare(strict_types=1);

namespace Application\Test\Unit\User;

use Application\Core\User\Exception\UserServiceException;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\Password;
use Application\Core\User\Repository\UserRepository;
use Application\Core\User\Service\UserService;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class UserServiceRegisterAdminTest extends TestCase
{
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserService $userService;

    /**
     * @throws DependencyException
     * @throws NotFoundException
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

        $existingAdmin = TestEntityBuilder::buildAdmin(['email' => 'first.admin@example.com']);
        $this->em->persist($existingAdmin);
        $this->em->flush();
        $this->em->clear();
    }

    public function testRejectsWeakPassword(): void
    {
        self::expectException(UserServiceException::class);

        $this->userService->registerAdmin(
            'joeNormal',
            'joe.normal@example.com',
            'peekaboo'
        );
    }

    public function testRejectsInvalidEmailAddress(): void
    {
        self::expectException(UserServiceException::class);

        $this->userService->registerAdmin(
            'joeNormal',
            'joe.normal#example.com',
            '#IAmDaBoss1234#'
        );
    }

    public function testPreventsDuplicateEmail(): void
    {
        self::expectException(UserServiceException::class);

        $this->userService->registerAdmin(
            'joeNormal',
            'first.admin@example.com',
            '#IAmDaBoss1234#'
        );
    }

    public function testCreatesNewAdmin(): void
    {
        $admin = $this->userService->registerAdmin(
            'joeNormal',
            'joe.normal@example.com',
            '#IAmDaBoss1234#'
        );

        $this->em->clear();

        $adminReloaded = $this->userRepository->findAdminById($admin->id());
        self::assertInstanceOf(Admin::class, $adminReloaded);
        self::assertEquals('joeNormal', $adminReloaded->name());
        self::assertEquals('joe.normal@example.com', $adminReloaded->email());
        self::assertTrue($adminReloaded->password()->equals(Password::fromPlainString('#IAmDaBoss1234#')));
    }
}
