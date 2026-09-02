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

class UserListTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    /** @var User[] */
    private array $users = [];
    private Admin $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);
        $this->users[] = $user;

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($this->admin);

        for ($i = 0; $i < 25; $i++) {
            $user = TestEntityBuilder::buildUser();
            $this->em->persist($user);
            $this->users[] = $user;
        }

        $this->em->flush();
        $this->em->clear();
    }

    public function testUserList(): void
    {
        $result = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/users')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);

        $resultDTOs = UserDetailsSwaggerDTO::fromArrayResponse($result->response());
        self::assertCount(count($this->users), $resultDTOs);
        self::assertEquals($this->users[0]->id(), $resultDTOs[0]->id);
    }

    public function testUserListWithPage(): void
    {
        $result = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/users')
            ->additionalGet(['per_page' => '5', 'page' => '1'])
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);

        $resultDTOs = UserDetailsSwaggerDTO::fromArrayResponse($result->response());

        self::assertCount(5, $resultDTOs);
        self::assertEquals($this->users[5]->id(), $resultDTOs[0]->id);
    }
}
