<?php

declare(strict_types=1);

namespace Application\Test\Integration\OAuth2;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\OAuth2\DTO\UserSessionDetailsSwaggerDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\User;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

final class OAuth2Test extends TestCase
{
    private HttpApplication $application;
    private User $user;
    private Admin $admin;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        TestApplicationFactory::injectOAuth2Configuration($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->user = TestEntityBuilder::buildUser();
        $this->user->enable();
        $this->em->persist($this->user);

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->admin->enable();
        $this->em->persist($this->admin);

        $this->em->flush();
        $this->em->clear();
    }

    public function testPublicEndpoint(): void
    {
        // no login
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/version')
            ->expectResponseCode(200)
            ->execute($this->application);

        // user
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/version')
            ->additionalServer(TestCase::authorizeUser($this->user))
            ->expectResponseCode(200)
            ->execute($this->application);

        // admin
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/version')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);
    }

    public function testAuthorizesUser(): void
    {
        // /ping/user should only be available for user

        // no login => unauthorized
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/ping/user')
            ->expectResponseCode(401)
            ->execute($this->application);

        // good login
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/ping/user')
            ->additionalServer(TestCase::authorizeUser($this->user))
            ->expectResponseCode(200)
            ->execute($this->application);

        // try as admin => denied
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/ping/user')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(403)
            ->execute($this->application);
    }

    public function testAuthorizesAdmin(): void
    {
        // no login => unauthorized
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/ping/admin')
            ->expectResponseCode(401)
            ->execute($this->application);

        // good login
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/ping/admin')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);

        // try as user => denied
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/ping/admin')
            ->additionalServer(TestCase::authorizeUser($this->user))
            ->expectResponseCode(403)
            ->execute($this->application);
    }

    public function testUserSession(): void
    {
        // unauthorized
        (new TestHttpRequestBuilder())
            ->method("GET")
            ->uri("/oauth2/user/session")
            ->expectResponseCode(401)
            ->execute($this->application);

        // login as user
        $responseContainer = (new TestHttpRequestBuilder())
            ->method("GET")
            ->uri("/oauth2/user/session")
            ->additionalServer(TestCase::authorizeUser($this->user))
            ->expectResponseCode(200)
            ->execute($this->application);

        $session = UserSessionDetailsSwaggerDTO::fromResponse($responseContainer->response());
        self::assertCount(1, $session->scopes);
        self::assertEquals("user", $session->scopes[0]);

        // login as admin
        $responseContainer = (new TestHttpRequestBuilder())
            ->method("GET")
            ->uri("/oauth2/user/session")
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);

        $session = UserSessionDetailsSwaggerDTO::fromResponse($responseContainer->response());
        self::assertCount(1, $session->scopes);
        self::assertEquals("admin", $session->scopes[0]);
    }

    public function testDisabledUser(): void
    {
        $this->user->disable();
        $this->em->persist($this->user);

        $this->admin->disable();
        $this->em->persist($this->admin);

        $this->em->flush();
        $this->em->clear();

        // Worked before (see above), now it shouldn't.
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/ping/user')
            ->additionalServer(TestCase::authorizeUser($this->user))
            ->expectResponseCode(401)
            ->execute($this->application);

        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/oauth2/user/session')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(401)
            ->execute($this->application);
    }
}
