<?php

namespace Application\Test\Integration\Desktop;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class DesktopGroupHttpTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;

    private Admin $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($this->admin);
        $this->em->flush();
    }

    public function testListDesktopGroup(): void
    {
        (new TestHttpRequestBuilder())
            ->method("GET")
            ->uri('/desktop_groups')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);
    }
}
