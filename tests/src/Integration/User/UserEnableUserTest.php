<?php

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\User;
use Application\Core\User\Repository\UserRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class UserEnableUserTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    private UserRepository $userRepository;

    private Admin $admin;
    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $userRepository = $this->application->container()->get(UserRepository::class);
        assert($userRepository instanceof UserRepository);
        $this->userRepository = $userRepository;

        $this->admin = TestEntityBuilder::buildAdmin(['email' => 'first.admin@example.com']);
        $this->em->persist($this->admin);

        $this->user = TestEntityBuilder::buildUser();
        $this->user->disable();
        $this->em->persist($this->user);

        $this->otherUser = TestEntityBuilder::buildUser();
        $this->otherUser->enable();
        $this->em->persist($this->otherUser);

        $this->em->flush();
        $this->em->clear();
    }

    public function testEnableUserFailsNotAdmin(): void
    {
        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri(
                sprintf(
                    '/user/%1$s/enable',
                    $this->user->id()
                )
            )
            ->additionalServer(TestCase::authorizeUser($this->otherUser))
            ->contentType("application/json")
            ->expectResponseCode(403)
            ->execute($this->application);
    }

    public function testEnableUserFailsUserNotFound(): void
    {
        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri(
                sprintf(
                    '/user/%1$s/enable',
                    129038
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(400)
            ->execute($this->application);
    }

    public function testEnableUser(): void
    {
        self::assertFalse($this->user->enabled());

        $responseContainer = (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri(
                sprintf(
                    '/user/%1$s/enable',
                    $this->user->id()
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $userDTO = UserDetailsSwaggerDTO::fromResponse($responseContainer->response());

        $reloadedUser = $this->userRepository->findUserById($this->user->id());
        self::assertInstanceOf(BaseUser::class, $reloadedUser);
        self::assertTrue($reloadedUser->enabled());
        self::assertEquals($reloadedUser->enabled(), $userDTO->enabled);
    }
}
