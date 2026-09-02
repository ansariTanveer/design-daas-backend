<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\UserGroup;
use Application\Test\TestApplicationFactory;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Application\Test\TestCase;

class UserGroupAddDesktopTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    private Admin $admin;
    private UserGroup $userGroup;
    private DesktopGroup $desktopGroup;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($this->admin);

        $this->userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($this->userGroup);

        $this->desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->em->persist($this->desktopGroup);

        $this->em->flush();
        $this->em->clear();
    }

    public function testAddsDesktopGroup(): void
    {
        (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri(sprintf('/user_group/%1$s/%2$s', $this->userGroup->id(), $this->desktopGroup->id()))
            ->contentType('application/json')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->expectResponseCode(200)
            ->execute($this->application);
    }
}
