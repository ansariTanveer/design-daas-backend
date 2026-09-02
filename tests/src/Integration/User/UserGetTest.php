<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\AdminDetailsSwaggerDTO;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\User;
use Application\Test\TestApplicationFactory;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Application\Test\TestCase;

class UserGetTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    private User $user;
    private Admin $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->user = TestEntityBuilder::buildUser();
        $this->em->persist($this->user);

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($this->admin);

        $this->em->flush();
        $this->em->clear();
    }

    public function testUserGet(): void
    {
        $result = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri(sprintf('/user/%1$d', $this->user->id()))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);

        $resultDTO = UserDetailsSwaggerDTO::fromResponse($result->response());
        self::assertEquals($this->user->id(), $resultDTO->id);
    }

    public function testAdminGet(): void
    {
        $result = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri(sprintf('/user/%1$d', $this->admin->id()))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);

        $resultDTO = AdminDetailsSwaggerDTO::fromResponse($result->response());
        self::assertEquals($this->admin->id(), $resultDTO->id);
    }
}
