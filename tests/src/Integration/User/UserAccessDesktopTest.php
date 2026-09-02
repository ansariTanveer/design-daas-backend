<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Desktop\Model\Desktop;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\Permissions\DTO\PermissionResultDTO;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\User\Model\User;
use Application\Core\User\Model\UserGroup;
use Application\Test\TestApplicationFactory;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Application\Test\TestCase;
use stdClass;

class UserAccessDesktopTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;
    private User $userAllowed;
    private User $userDenied;
    private UserGroup $userGroup;
    private Desktop $desktop;
    private DesktopGroup $desktopGroup;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->userAllowed = TestEntityBuilder::buildUser();
        $this->userAllowed->enable();
        $this->em->persist($this->userAllowed);

        $this->userDenied = TestEntityBuilder::buildUser();
        $this->userDenied->enable();
        $this->em->persist($this->userDenied);

        $this->userGroup = TestEntityBuilder::buildUserGroup();
        $this->userGroup->addUser($this->userAllowed);
        $this->em->persist($this->userGroup);

        $this->desktop = TestEntityBuilder::buildDesktop();
        $this->em->persist($this->desktop);

        $this->desktopGroup = TestEntityBuilder::buildDesktopGroup();
        $this->desktopGroup->addDesktop($this->desktop);
        // Finally, associate the 2 groups
        $this->desktopGroup->addUserGroup($this->userGroup);
        $this->em->persist($this->desktopGroup);

        $this->em->flush();
        $this->em->clear();
    }

    public function testUserAccessMissingDesktop(): void
    {
        $response = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri(sprintf('/users/access/%1$d', 1234567))
            ->contentType('application/json')
            ->additionalServer(TestCase::authorizeUser($this->userDenied))
            ->expectResponseCode(404)
            ->execute($this->application);
    }

    public function testUserAccessDenied(): void
    {
        $response = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri(sprintf('/users/access/%1$d', $this->desktop->id()))
            ->contentType('application/json')
            ->additionalServer(TestCase::authorizeUser($this->userDenied))
            ->expectResponseCode(200)
            ->execute($this->application);

        $json = json_decode($response->bodyAsString());
        self::assertInstanceOf(StdClass::class, $json);
        $access = PermissionResultDTO::fromJson($json);
        self::assertEquals(AccessEnum::DENY->value, $access->result);
        self::assertEquals($this->userDenied->id(), $access->user->id);
    }

    public function testUserAccessAllowed(): void
    {
        $response = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri(sprintf('/users/access/%1$d', $this->desktop->id()))
            ->contentType('application/json')
            ->additionalServer(TestCase::authorizeUser($this->userAllowed))
            ->expectResponseCode(200)
            ->execute($this->application);

        $json = json_decode($response->bodyAsString());
        self::assertInstanceOf(StdClass::class, $json);
        $access = PermissionResultDTO::fromJson($json);
        self::assertEquals(AccessEnum::ALLOW->value, $access->result);
        self::assertEquals($this->userAllowed->id(), $access->user->id);
    }
}
