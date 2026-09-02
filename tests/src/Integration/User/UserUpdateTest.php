<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\User;
use Application\Test\TestApplicationFactory;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Application\Test\TestCase;

class UserUpdateTest extends TestCase
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

    public function testUpdateUser(): void
    {
        $requestDTO = new UserDetailsSwaggerDTO();
        $requestDTO->name = 'Test User';
        $requestDTO->email = 'test@example.com';

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        (new TestHttpRequestBuilder())
            ->method('PATCH')
            ->uri(sprintf('/user/%1$s', $this->user->id()))
            ->body($requestJson)
            ->contentType('application/json')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);
    }
}
