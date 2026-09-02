<?php

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\DTO\UserEmailVerificationDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\User;
use Application\Core\User\Repository\UserRepository;
use Application\Core\User\Service\UserService;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use BjoernGoetschke\DateTime\Interval;
use BjoernGoetschke\DateTime\Moment;
use Doctrine\ORM\EntityManagerInterface;

class UserValidateEmailTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $userRepository = $this->application->container()->get(UserRepository::class);
        assert($userRepository instanceof UserRepository);
        $this->userRepository = $userRepository;

        $this->user = TestEntityBuilder::buildUser();
        $this->em->persist($this->user);

        $this->em->flush();
    }

    public function testValidateEmailFailsWrongCode(): void
    {
        $requestDto = new UserEmailVerificationDTO();
        $requestDto->email = $this->user->email();
        $requestDto->registration_code = "gibberish";

        $requestJson = json_encode($requestDto);
        self::assertIsString($requestJson);

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri("/user/validate_email")
            ->body($requestJson)
            ->contentType("application/json")
            ->expectResponseCode(400)
            ->execute($this->application);
    }

    public function testUserValidateEmail(): void
    {
        self::assertFalse($this->user->enabled());
        self::assertNull($this->user->registrationUsedMoment());

        $requestDto = new UserEmailVerificationDTO();
        $requestDto->email = $this->user->email();
        $requestDto->registration_code = $this->user->registrationCode();

        $requestJson = json_encode($requestDto);
        self::assertIsString($requestJson);

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri("/user/validate_email")
            ->body($requestJson)
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $userReloaded = $this->userRepository->findUserById($this->user->id());
        self::assertInstanceOf(User::class, $userReloaded);
        self::assertTrue($userReloaded->enabled());
        self::assertNotNull($userReloaded->registrationUsedMoment());
    }

    public function testValidateEmailFailsInvalidBody(): void
    {
        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri("/user/validate_email")
            ->body('')
            ->contentType("application/json")
            ->expectResponseCode(400)
            ->execute($this->application);
    }
}
