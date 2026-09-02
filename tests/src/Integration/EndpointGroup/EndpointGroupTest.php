<?php

namespace Application\Test\Integration\EndpointGroup;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

class EndpointGroupTest extends TestCase
{
    private HttpApplication $application;
    private Admin $admin;

    private EntityManagerInterface $em;

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

    public function testDeleteEndpointGroupUserGroupAccess(): void
    {
        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $userGroup = TestEntityBuilder::buildUserGroup();
        $endpointGroupUserGroup = TestEntityBuilder::buildEndpointGroupUserGroupAccess(
            $endpointGroup,
            $userGroup
        );

        $this->em->persist($endpointGroup);
        $this->em->persist($userGroup);
        $this->em->persist($endpointGroupUserGroup);
        $this->em->flush();

        $url = sprintf('/endpoint_group/%1$s/%2$d', $endpointGroup->uniqueGroupName(), $userGroup->id());

        (new TestHttpRequestBuilder())
            ->method("DELETE")
            ->uri($url)
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);
    }

    public function testDeleteEndpointGroupUserGroupAccessFailsWithInvalidParameters(): void
    {

        $url = sprintf('/endpoint_group/%1$s/%2$d', 'test_group', 123);

        (new TestHttpRequestBuilder())
            ->method("DELETE")
            ->uri($url)
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(400)
            ->execute($this->application);
    }
}
