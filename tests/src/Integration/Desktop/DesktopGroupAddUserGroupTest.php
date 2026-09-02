<?php

declare(strict_types=1);

namespace Application\Test\Integration\Desktop;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\User\DTO\UserGroupDetailsDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\UserGroup;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;

class DesktopGroupAddUserGroupTest extends TestCase
{
    private HttpApplication $application;
    private DesktopGroup $desktopGroup;
    private UserGroup $userGroup;

    private Admin $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $em = TestApplicationFactory::extractEntityManager($this->application);

        $this->desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $em->persist($this->desktopGroup);

        $this->userGroup = TestEntityBuilder::buildUserGroup();
        $em->persist($this->userGroup);

        $this->admin = TestEntityBuilder::buildAdmin();
        $em->persist($this->admin);
        $em->flush();
    }

    public function testAddsUserGroupToDesktopGroup(): void
    {
        $desktopGroupId = $this->desktopGroup->id();

        $responseContainer = (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri(sprintf('/desktop_group/%1$d/user_group/%2$d', $desktopGroupId, $this->userGroup->id()))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $response = $responseContainer->response();
        $associatedUserGroups = UserGroupDetailsDTO::fromArrayResponse($response);

        self::assertCount(1, $associatedUserGroups);
        self::assertCount(1, $this->desktopGroup->userGroups());
        self::assertEquals($this->userGroup->id(), $associatedUserGroups[0]->id);
        self::assertEquals($this->userGroup->description(), $associatedUserGroups[0]->description);
    }

    public function testAddsUserGroupToDesktopGroupTwice(): void
    {
        $desktopGroupId = $this->desktopGroup->id();

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri(sprintf('/desktop_group/%1$d/user_group/%2$d', $desktopGroupId, $this->userGroup->id()))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri(sprintf('/desktop_group/%1$d/user_group/%2$d', $desktopGroupId, $this->userGroup->id()))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(409)
            ->execute($this->application);
    }

    public function testAddsUserGroupToDesktopGroupNotFound(): void
    {
        $desktopGroupId = $this->desktopGroup->id();

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri(sprintf('/desktop_group/%1$d/user_group/%2$d', 42, $this->userGroup->id()))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(404)
            ->execute($this->application);

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri(sprintf('/desktop_group/%1$d/user_group/%2$d', $desktopGroupId, 42))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(404)
            ->execute($this->application);
    }
}
