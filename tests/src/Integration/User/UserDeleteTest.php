<?php

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class UserDeleteTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        TestApplicationFactory::injectOAuth2Configuration($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);
    }

    public function testAdminDeleteAction(): void
    {
        $admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($admin);

        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();

        (new TestHttpRequestBuilder())
            ->method('DELETE')
            ->uri(sprintf('/user/%1$s', $user->id()))
            ->expectResponseCode(200)
            ->additionalServer(TestCase::authorizeAdmin($admin))
            ->execute($this->application);
    }
}
