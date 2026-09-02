<?php

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Core\Permissions\Repository\EndpointGroupRepository;
use Application\Core\Permissions\Service\PermissionsService;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class PermissionsServiceTest extends TestCase
{
    private const SAMPLE_JSON = '{
      "unique_group_name": "common",
      "group_description": "Routes for common tasks.",
      "permission_id_from": 60000,
      "permission_id_to": 69999,
      "endpoint_file": "/home/design-daas/backend/web/daas_web/routing/common_routes.py",
      "endpoints": [
        {
          "unique_permission_id": 60000,
          "function_name": "index",
          "endpoint_url": "/",
          "request_method": "get",
          "function_desc": "",
          "function_params": []
        },
        {
          "unique_permission_id": 60001,
          "function_name": "debug",
          "endpoint_url": "/debug",
          "request_method": "patch",
          "function_desc": "",
          "function_params": []
        }
      ]
    }';

    private PermissionsService $sut;
    private EndpointGroupRepository $endpointGroupRepository;
    private EntityManagerInterface $em;


    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($application);

        $this->em = TestApplicationFactory::extractEntityManager($application);

        /** @var PermissionsService $sut */
        $sut = $application->container()->get(PermissionsService::class);
        $this->sut = $sut;

        /** @var EndpointGroupRepository $endpoointGroupRepository */
        $endpoointGroupRepository = $application->container()->get(EndpointGroupRepository::class);
        $this->endpointGroupRepository = $endpoointGroupRepository;
    }

    public function testStoresAndRestoresEndpoint(): void
    {
        $stream = fopen('php://temp', 'w+');
        assert(is_resource($stream));

        fwrite($stream, self::SAMPLE_JSON);
        rewind($stream);

        $this->sut->updatePermissionsFromStream($stream);

        $this->em->clear();

        $allEndpointGroups = $this->endpointGroupRepository->findAll();
        self::assertCount(1, $allEndpointGroups);

        $endpointGroup = $allEndpointGroups[0];
        self::assertEquals('common', $endpointGroup->uniqueGroupName());
        self::assertCount(2, $endpointGroup->endpoints());

        self::assertEquals(60000, $endpointGroup->endpoints()[0]->id());
        self::assertEquals('/', $endpointGroup->endpoints()[0]->endpointUrl());
        self::assertEquals('index', $endpointGroup->endpoints()[0]->functionName());
        self::assertEquals('get', $endpointGroup->endpoints()[0]->method());

        self::assertEquals(60001, $endpointGroup->endpoints()[1]->id());
        self::assertEquals('/debug', $endpointGroup->endpoints()[1]->endpointUrl());
        self::assertEquals('debug', $endpointGroup->endpoints()[1]->functionName());
        self::assertEquals('patch', $endpointGroup->endpoints()[1]->method());
    }
}
