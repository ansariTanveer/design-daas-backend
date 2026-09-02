<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\UserGroupDetailsDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\UserGroup;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class UserGroupListingTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;

    private Admin $admin;

    /** @var UserGroup[] */
    private array $groups;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($this->admin);

        $this->groups = [];
        for ($i = 0; $i < 5; $i++) {
            $this->groups[] = TestEntityBuilder::buildUserGroup();
            $this->em->persist($this->groups[count($this->groups) - 1]);
        }

        $this->em->flush();
    }

    public function testListsUserGroups(): void
    {
        $responseContainer = (new TestHttpRequestBuilder())
            ->method("GET")
            ->uri("/user_groups")
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $groupDTOs = UserGroupDetailsDTO::fromArrayResponse($responseContainer->response());
        self::assertCount(count($this->groups), $groupDTOs);

        for ($i = 0; $i < count($groupDTOs); $i++) {
            $group = $this->groups[$i];
            $dto = $groupDTOs[$i];

            self::assertSame($group->id(), $dto->id);
            self::assertSame($group->description(), $dto->description);
        }
    }
}
