<?php

declare(strict_types=1);

namespace Application\Test\Integration\Permissions;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\Model\Admin;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

class PermissionsTest extends TestCase
{
    private EntityManagerInterface $em;
    private HttpApplication $application;
    private Admin $admin;
    private EndpointFixture $endpointFixture;

    /**
     * @throws NotFoundException
     * @throws DependencyException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $this->endpointFixture = $endpointFixture;

        $this->admin = TestEntityBuilder::buildAdmin();
        $this->em->persist($this->admin);

        $this->em->flush();
    }

    public function testListsPermission(): void
    {
        $data = $this->endpointFixture->load();
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri(
                sprintf(
                    '/permissions/%1$s/%2$s',
                    $data->endpoint->functionName(),
                    $data->user->id()
                )
            )
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);
    }
}
