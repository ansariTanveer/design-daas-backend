<?php

declare(strict_types=1);

namespace Application\Test\Unit\User;

use Application\Common\Application\ApplicationInterface;
use Application\Core\User\DTO\UserCreationDTO;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\DTO\UserEmailVerificationDTO;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Exception\UserGroupException;
use Application\Core\User\Exception\UserServiceException;
use Application\Core\User\Enum\UserGetDetailsResult;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\Password;
use Application\Core\User\Model\User;
use Application\Core\User\Model\UserGroup;
use Application\Core\User\Repository\UserRepository;
use Application\Core\User\Service\UserService;
use Application\Core\Util\Mail\Mail;
use Application\Core\Util\Mail\MailLanguageEnum;
use Application\Core\Util\Mail\MailQueueService;
use Application\Core\Util\Mail\MailTypeEnum;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use BjoernGoetschke\DateTime\Interval;
use BjoernGoetschke\DateTime\Moment;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class UserServiceTest extends TestCase
{
    private ApplicationInterface $application;
    private UserService $sut;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private UserGroup $testUserGroup;

    /** @var (MailQueueService&MockObject) */
    private MockObject $mockMailQueueService;

    /** @var array<User> */
    private array $testUsers;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->mockMailQueueService = $this->createMock(MailQueueService::class);
        $this->application->container()->set(MailQueueService::class, $this->mockMailQueueService);

        $sut = $this->application->container()->get(UserService::class);
        assert($sut instanceof UserService);
        $this->sut = $sut;

        $userRepository = $this->application->container()->get(UserRepository::class);
        assert($userRepository instanceof UserRepository);
        $this->userRepository = $userRepository;

        $this->testUserGroup = TestEntityBuilder::buildUserGroup(["description" => "Lorem ipsum dolor sit amet"]);
        $this->em->persist($this->testUserGroup);

        $this->testUsers = [];
        for ($i = 0; $i < 2; $i++) {
            $testUser = TestEntityBuilder::buildUser();
            $this->em->persist($testUser);

            $this->testUsers[] = $testUser;
        }

        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @throws UserServiceException
     * @throws BaseUserException
     * @throws UserGroupException
     */
    public function testCreatesUser(): void
    {
        $requestDTO = new UserCreationDTO();
        $requestDTO->name = "Test User";
        $requestDTO->email = "test@example.com";
        $requestDTO->password = "!SuperSecretPassword";

        // expect a confirmation mail to be sent
        $this->mockMailQueueService
            ->expects(self::once())
            ->method('enqueue')
            ->willReturnCallback(function (Mail $mail): void {
                self::assertEquals('test@example.com', $mail->recipientMail());

                // mail text loaded?
                self::assertStringContainsString('confirm your e-Mail', $mail->body());
                // but registration code placeholder replaced?
                self::assertStringNotContainsString('%registration_code%', $mail->body());
            });

        $newUser = $this->sut->registerUser($requestDTO);
        $this->em->flush();
        $this->em->clear();

        $newUserReloaded = $this->userRepository->findUserById($newUser->id());
        self::assertInstanceOf(User::class, $newUserReloaded);
        self::assertSame($requestDTO->name, $newUserReloaded->name());
        self::assertSame($requestDTO->email, $newUserReloaded->email());
        self::assertTrue($newUserReloaded->password()->isHashed());
        self::assertCount(0, $newUserReloaded->groups()); // regression test for DES-138
        self::assertTrue($newUserReloaded->password()->equals(Password::fromPlainString($requestDTO->password)));
        self::assertMatchesRegularExpression(
            '/^[a-zA-Z0-9]{' . BaseUser::REGISTRATION_CODE_LENGTH . '}$/',
            $newUserReloaded->registrationCode()
        );
        self::assertFalse($newUserReloaded->registrationCodeExpired());
    }

    /**
     * @throws BaseUserException
     * @throws UserGroupException
     */
    public function testCreatesUserDuplicateEmailAddress(): void
    {
        self::expectException(UserServiceException::class);

        $requestDTO = new UserCreationDTO();
        $requestDTO->name = "Test User";
        $requestDTO->email = "dummy@example.com";
        $requestDTO->password = "!SuperSecretPassword";

        $this->sut->registerUser($requestDTO);
        $this->em->flush();
        $this->em->clear();
        // This should trigger exception
        $this->sut->registerUser($requestDTO);
    }

    /**
     * @throws BaseUserException
     * @throws UserGroupException
     */
    public function testCreatesUserInvalidEmailAddress(): void
    {
        self::expectException(UserServiceException::class);

        $requestDTO = new UserCreationDTO();
        $requestDTO->name = "Test User";
        $requestDTO->email = "this is not an email address";
        $requestDTO->password = "!SuperSecretPassword";

        $this->sut->registerUser($requestDTO);
    }

    /**
     * @throws BaseUserException
     * @throws UserGroupException
     */
    public function testCreatesUserInvalidPassword(): void
    {
        self::expectException(UserServiceException::class);

        $requestDTO = new UserCreationDTO();
        $requestDTO->name = "Test User";
        $requestDTO->email = "normal@example.com";
        $requestDTO->password = "123";

        $this->sut->registerUser($requestDTO);
    }

    public function testListUsersWithPagination(): void
    {
        // Add additional users only for this case
        for ($i = 0; $i < 18; $i++) {
            $testUser = TestEntityBuilder::buildUser();
            $this->em->persist($testUser);

            $this->testUsers[] = $testUser;
        }

        $this->em->flush();
        $this->em->clear();

        $result = $this->sut->userListPage(5, 1);

        self::assertCount(5, $result);

        self::assertInstanceOf(UserDetailsSwaggerDTO::class, $result[0]);
        self::assertEquals($this->testUsers[5]->id(), $result[0]->id);
        self::assertEquals($this->testUsers[5]->name(), $result[0]->name);
        self::assertEquals($this->testUsers[5]->email(), $result[0]->email);
        self::assertEquals($this->testUsers[5]->groups(), $result[0]->groups);
        self::assertEquals($this->testUsers[5]->enabled(), $result[0]->enabled);
        self::assertEquals('user', $result[0]->role);

        self::assertInstanceOf(UserDetailsSwaggerDTO::class, $result[1]);
        self::assertEquals($this->testUsers[6]->id(), $result[1]->id);
        self::assertEquals($this->testUsers[6]->name(), $result[1]->name);
        self::assertEquals($this->testUsers[6]->email(), $result[1]->email);
        self::assertEquals($this->testUsers[6]->groups(), $result[1]->groups);
        self::assertEquals($this->testUsers[6]->enabled(), $result[1]->enabled);
    }

    public function testUserGetDetailsReturnsUserDetails(): void
    {
        $testUser = $this->testUsers[1];

        $resultObject = $this->sut->userGetDetails($testUser->id());

        self::assertEquals(UserGetDetailsResult::OK, $resultObject->result);
        self::assertNotNull($resultObject->userDetailsDTO);
        self::assertEquals($resultObject->userDetailsDTO->id, $testUser->id());
    }

    public function testUserGetDetailsFailsOnInvalidId(): void
    {
        $resultObject = $this->sut->userGetDetails(4711);

        self::assertEquals(UserGetDetailsResult::USER_NOT_FOUND, $resultObject->result);
        self::assertNull($resultObject->userDetailsDTO);
    }

    /**
     * @throws UserServiceException
     */
    public function testUserCanValidateEmail(): void
    {
        $dto = new UserEmailVerificationDTO();
        $dto->email = $this->testUsers[1]->email();
        $dto->registration_code = $this->testUsers[1]->registrationCode();

        $this->sut->validateEmailRegistrationCode($dto);

        $userReloaded = $this->userRepository->findUserById($this->testUsers[1]->id());

        self::assertInstanceOf(User::class, $userReloaded);
        self::assertTrue($userReloaded->enabled());
    }

    public function testUserValidateEmailFailsWrongEmail(): void
    {
        self::expectException(UserServiceException::class);

        $dto = new UserEmailVerificationDTO();
        $dto->email = $this->testUsers[0]->email();
        $dto->registration_code = $this->testUsers[1]->registrationCode();

        $this->sut->validateEmailRegistrationCode($dto);
    }

    public function testUserValidateEmailFailsWrongCode(): void
    {
        self::expectException(UserServiceException::class);

        $dto = new UserEmailVerificationDTO();
        $dto->email = $this->testUsers[1]->email();
        $dto->registration_code = $this->testUsers[0]->registrationCode();

        $this->sut->validateEmailRegistrationCode($dto);
    }

    public function testDeleteAdmin(): void
    {
        $admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($admin);

        $this->em->flush();
        $this->em->clear();

        $this->sut->deleteAdmin($admin->id());

        $this->em->clear();

        self::assertNull($this->userRepository->findUserById($admin->id()));
    }

    public function testDeleteAdminFailsOnUserId(): void
    {
        self::expectException(UserServiceException::class);

        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();

        $this->sut->deleteAdmin($user->id());
    }

    public function testDeleteAdminFailsOnInvalidId(): void
    {
        self::expectException(UserServiceException::class);

        $this->sut->deleteAdmin(44444);
    }

    public function testDeleteUser(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();

        $this->sut->deleteUser($user->id());

        $this->em->clear();

        self::assertNull($this->userRepository->findUserById($user->id()));
    }

    public function testDeleteUserFailsOnAdminId(): void
    {
        self::expectException(UserServiceException::class);

        $admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($admin);

        $this->em->flush();
        $this->em->clear();

        $this->sut->deleteUser($admin->id());
    }

    public function testDeleteUserFailsOnInvalidId(): void
    {
        self::expectException(UserServiceException::class);

        $this->sut->deleteUser(44444);
    }

    public function testUserRequestApplication(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();

        $this->mockMailQueueService
            ->expects(self::once())
            ->method('enqueue')
            ->willReturnCallback(
                function (Mail $mail) use ($user): void {
                    self::assertEquals(MailTypeEnum::APPLICATION_REQUEST, $mail->type());
                    self::assertEquals($user->email(), $mail->recipientMail());

                    // german language template?
                    self::assertStringContainsString('eine Anfrage zukommen', $mail->body());
                    self::assertStringContainsString('bobApplication', $mail->body());
                },
            );

        $this->sut->requestApplication($user, 'bobApplication', MailLanguageEnum::DEU);
    }
}
