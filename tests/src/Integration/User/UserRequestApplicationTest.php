<?php

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class UserRequestApplicationTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    protected function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);
    }

    public function testRequestApplication(): void
    {
        $user = TestEntityBuilder::buildUser();
        $user->enable();
        $this->em->persist($user);

        $this->em->flush();
        $this->em->clear();

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri('/user/request-application/someApp/deu')
            ->additionalServer(TestCase::authorizeUser($user))
            ->expectResponseCode(200)
            ->execute($this->application);
    }
}
