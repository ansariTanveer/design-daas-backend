<?php

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\AdminDetailsSwaggerDTO;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;

class AdminListActionTest extends TestCase
{
    private HttpApplication $application;
    private Admin $admin;
    private Admin $anotherAdmin;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        TestApplicationFactory::injectOAuth2Configuration($this->application);
        $em = TestApplicationFactory::extractEntityManager($this->application);

        $this->admin = TestEntityBuilder::buildAdmin();
        $em->persist($this->admin);

        $this->anotherAdmin = TestEntityBuilder::buildAdmin();
        $em->persist($this->anotherAdmin);

        $em->flush();
        $em->clear();
    }

    public function testListsAdmins(): void
    {
        $result = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/admins')
            ->expectResponseCode(200)
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->execute($this->application);

        $resultJson = json_decode($result->bodyAsString());
        assert(is_array($resultJson));

        self::assertCount(2, $resultJson);

        $result = AdminDetailsSwaggerDTO::fromJson($resultJson[0]);
        self::assertInstanceOf(AdminDetailsSwaggerDTO::class, $result);
        self::assertEquals($this->admin->name(), $result->name);
        self::assertEquals($this->admin->email(), $result->email);

        $result = AdminDetailsSwaggerDTO::fromJson($resultJson[1]);
        self::assertInstanceOf(AdminDetailsSwaggerDTO::class, $result);
        self::assertEquals($this->anotherAdmin->name(), $result->name);
        self::assertEquals($this->anotherAdmin->email(), $result->email);
    }
}
