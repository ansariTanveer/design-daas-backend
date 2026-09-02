<?php

declare(strict_types=1);

namespace Application\Test\Integration\Permissions;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Repository\EndpointGroupUserGroupAccessRepository;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class EndpointGroupAddUserGroupAccessTest extends TestCase
{
    private EntityManagerInterface $em;
    private HttpApplication $application;
    private Admin $admin;

    private EndpointGroupUserGroupAccessRepository $repo;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $repo = $this->application->container()->get(EndpointGroupUserGroupAccessRepository::class);
        assert($repo instanceof EndpointGroupUserGroupAccessRepository);
        $this->repo = $repo;

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($this->admin);

        $this->em->flush();
    }

    /**
     * @throws BaseUserException
     */
    public function testAddEndPointGroupUserGroupAccessAllow(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($user);
        $this->em->persist($userGroup);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $this->em->flush();

        (new TestHttpRequestBuilder())
            ->method('PUT')
            ->uri(
                sprintf(
                    '/endpoint_group/%1$s/%2$s/allow',
                    $endpointGroup->uniqueGroupName(),
                    $userGroup->id()
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);

        $endpointGroupUserGroupAccesses = $this->repo->findAll();
        self::assertCount(1, $endpointGroupUserGroupAccesses);
        self::assertEquals(AccessEnum::ALLOW, $endpointGroupUserGroupAccesses[0]->relation());
    }

    /**
     * @throws BaseUserException
     */
    public function testAddEndPointGroupUserGroupAccessDeny(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($user);
        $this->em->persist($userGroup);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $this->em->flush();

        (new TestHttpRequestBuilder())
            ->method('PUT')
            ->uri(
                sprintf(
                    '/endpoint_group/%1$s/%2$s/deny',
                    $endpointGroup->uniqueGroupName(),
                    $userGroup->id()
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);

        $endpointGroupUserGroupAccesses = $this->repo->findAll();
        self::assertCount(1, $endpointGroupUserGroupAccesses);
        self::assertEquals(AccessEnum::DENY, $endpointGroupUserGroupAccesses[0]->relation());
    }

    /**
     * @throws BaseUserException
     */
    public function testAddEndPointGroupUserGroupAccessFailsWrongPermissionValue(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($user);
        $this->em->persist($userGroup);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $this->em->flush();

        //Returns HTTP 404 as the value is wrong and not in the actual route
        (new TestHttpRequestBuilder())
            ->method('PUT')
            ->uri(
                sprintf(
                    '/endpoint_group/%1$s/%2$s/test',
                    $endpointGroup->uniqueGroupName(),
                    $userGroup->id()
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType('application/json')
            ->expectResponseCode(404)
            ->execute($this->application);
    }

    /**
     * @throws BaseUserException
     */
    public function testAddEndPointGroupUserGroupAccessFailsWrongValue(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($user);
        $this->em->persist($userGroup);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $this->em->flush();

        (new TestHttpRequestBuilder())
            ->method('PUT')
            ->uri(
                sprintf(
                    '/endpoint_group/%1$s/%2$s/test',
                    189237,
                    198237
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType('application/json')
            ->expectResponseCode(404)
            ->execute($this->application);
    }

    public function testDoesNothingIfAccessAlreadyExists(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($user);
        $this->em->persist($userGroup);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $access = TestEntityBuilder::buildEndpointGroupUserGroupAccess(
            $endpointGroup,
            $userGroup,
            ['relation' => AccessEnum::ALLOW ]
        );
        $this->em->persist($access);

        $this->em->flush();

        (new TestHttpRequestBuilder())
            ->method('PUT')
            ->uri(
                sprintf(
                    '/endpoint_group/%1$s/%2$s/allow',
                    $endpointGroup->uniqueGroupName(),
                    $userGroup->id()
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);

        $endpointGroupUserGroupAccesses = $this->repo->findAll();

        //Just one access, no new elements were added
        self::assertCount(1, $endpointGroupUserGroupAccesses);
        //Relation type did not change (no changes)
        self::assertEquals(AccessEnum::ALLOW, $endpointGroupUserGroupAccesses[0]->relation());
    }

    public function testUpdatesRelationIfAccessExists(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($user);
        $this->em->persist($userGroup);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $access = TestEntityBuilder::buildEndpointGroupUserGroupAccess(
            $endpointGroup,
            $userGroup,
            ['relation' => AccessEnum::ALLOW ]
        );
        $this->em->persist($access);

        $this->em->flush();

        (new TestHttpRequestBuilder())
            ->method('PUT')
            ->uri(
                sprintf(
                    '/endpoint_group/%1$s/%2$s/deny',
                    $endpointGroup->uniqueGroupName(),
                    $userGroup->id()
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);

        $endpointGroupUserGroupAccesses = $this->repo->findAll();

        //Just one access, no new elements were added
        self::assertCount(1, $endpointGroupUserGroupAccesses);
        //But with a different relation, now DENY
        self::assertEquals(AccessEnum::DENY, $endpointGroupUserGroupAccesses[0]->relation());

        (new TestHttpRequestBuilder())
            ->method('PUT')
            ->uri(
                sprintf(
                    '/endpoint_group/%1$s/%2$s/allow',
                    $endpointGroup->uniqueGroupName(),
                    $userGroup->id()
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);

        $endpointGroupUserGroupAccesses = $this->repo->findAll();

        //Just one access, no new elements were added
        self::assertCount(1, $endpointGroupUserGroupAccesses);
        //But with a different value - now it should be ALLOW (proof of change)
        self::assertEquals(AccessEnum::ALLOW, $endpointGroupUserGroupAccesses[0]->relation());
    }
}
