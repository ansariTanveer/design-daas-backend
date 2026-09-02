<?php

/** @noinspection SpellCheckingInspection */

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Core\Permissions\DTO\InfrastructurePermissionsDTO;
use Application\Test\TestCase;
use stdClass;

final class InfrastructurePermissionsDTOTest extends TestCase
{
    private const SAMPLE_JSON = '{
        "unique_group_name": "node",
        "group_description": "Routes for node management tasks.",
        "permission_id_from": 70000,
        "permission_id_to": 79999,
        "endpoint_file": "/home/design-daas/backend/web/daas_web/routing/node_routes.py",
        "endpoints": [
            {
                "unique_permission_id": 70000,
                "function_name": "node_vmconfigure_dhcp",
                "endpoint_url": "/node/vmconfigure_dhcp",
                "request_method": "post",
                "function_desc": "",
                "function_params": []
            },
            {
                "unique_permission_id": 70001,
                "function_name": "node_vmconfigure_iptables",
                "endpoint_url": "/node/vmconfigure_iptables",
                "request_method": "post",
                "function_desc": "",
                "function_params": []
            },
            {
                "unique_permission_id": 70002,
                "function_name": "node_vminvoke_upload",
                "endpoint_url": "/node/vminvoke_upload",
                "request_method": "post",
                "function_desc": "",
                "function_params": []
            }
        ]
    }';

    public function testReadsSampleData(): void
    {
        $json = json_decode(self::SAMPLE_JSON);
        assert($json instanceof stdClass);

        $infrastructurePermissionDTO = InfrastructurePermissionsDTO::fromJson($json);
        self::assertEquals(
            'node',
            $infrastructurePermissionDTO->unique_group_name
        );
        self::assertEquals(
            'Routes for node management tasks.',
            $infrastructurePermissionDTO->group_description
        );
        self::assertEquals(
            70000,
            $infrastructurePermissionDTO->permission_id_from
        );
        self::assertEquals(
            79999,
            $infrastructurePermissionDTO->permission_id_to
        );
        self::assertEquals(
            '/home/design-daas/backend/web/daas_web/routing/node_routes.py',
            $infrastructurePermissionDTO->endpoint_file
        );

        self::assertCount(3, $infrastructurePermissionDTO->endpoints);

        self::assertEquals(
            70000,
            $infrastructurePermissionDTO->endpoints[0]->unique_permission_id
        );
        self::assertEquals(
            'node_vmconfigure_dhcp',
            $infrastructurePermissionDTO->endpoints[0]->function_name
        );
        self::assertEquals(
            '/node/vmconfigure_dhcp',
            $infrastructurePermissionDTO->endpoints[0]->endpoint_url
        );
        self::assertEquals(
            'post',
            $infrastructurePermissionDTO->endpoints[0]->request_method
        );
        self::assertEquals(
            '',
            $infrastructurePermissionDTO->endpoints[0]->function_desc
        );

        self::assertEquals(
            70001,
            $infrastructurePermissionDTO->endpoints[1]->unique_permission_id
        );
        self::assertEquals(
            'node_vmconfigure_iptables',
            $infrastructurePermissionDTO->endpoints[1]->function_name
        );
        self::assertEquals(
            '/node/vmconfigure_iptables',
            $infrastructurePermissionDTO->endpoints[1]->endpoint_url
        );
        self::assertEquals(
            'post',
            $infrastructurePermissionDTO->endpoints[1]->request_method
        );
        self::assertEquals(
            '',
            $infrastructurePermissionDTO->endpoints[1]->function_desc
        );

        self::assertEquals(
            70002,
            $infrastructurePermissionDTO->endpoints[2]->unique_permission_id
        );
        self::assertEquals(
            'node_vminvoke_upload',
            $infrastructurePermissionDTO->endpoints[2]->function_name
        );
        self::assertEquals(
            '/node/vminvoke_upload',
            $infrastructurePermissionDTO->endpoints[2]->endpoint_url
        );
        self::assertEquals(
            'post',
            $infrastructurePermissionDTO->endpoints[2]->request_method
        );
        self::assertEquals(
            '',
            $infrastructurePermissionDTO->endpoints[2]->function_desc
        );
    }
}
