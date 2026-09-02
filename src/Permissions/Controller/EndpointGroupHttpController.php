<?php

namespace Application\Core\Permissions\Controller;

use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Core\Permissions\Exception\EndpointGroupException;
use Application\Core\Permissions\Exception\EndpointGroupUserGroupAccessException;
use Application\Core\Permissions\Service\EndpointGroupService;
use Application\Core\User\Exception\UserGroupException;
use Doctrine\ORM\EntityNotFoundException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final readonly class EndpointGroupHttpController
{
    public function __construct(
        private EndpointGroupService $endpointGroupService,
        private HttpResponseFactory $responseFactory,
    ) {
    }

    /**
     * @psalm-param non-empty-string $endpointGroupName
     * @psalm-param positive-int $userGroupId
     * @psalm-param 'allow'|'deny' $access
     */
    #[OA\Put(
        path: '/endpoint_group/{endpointGroupName}/{userGroupId}/{access}',
        operationId: 'endpoint_group_user_group_access',
        security: [['oauth2-user' => ['admin']]],
        tags: ['Permissions'],
        parameters: [
            new OA\Parameter(
                name:        'endpointGroupName',
                description: 'Endpoint group name',
                in:          'path',
                schema:      new OA\Schema(
                    type:      'string',
                    minLength: 1,
                    pattern:   '^.+$'
                ),
            ),
            new OA\Parameter(
                name:        'userGroupId',
                description: 'User Group ID',
                in:          'path',
                schema:      new OA\Schema(type: 'integer', minimum: 1),
                example:     1,
            ),
            new OA\Parameter(
                name:        'access',
                description: 'allow/deny',
                in:          'path',
                required:    true,
                schema:      new OA\Schema(
                    type:    'string',
                    pattern: '^(allow|deny)$',
                    enum:    ['allow', 'deny'],
                ),
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: 'Endpoint group user group access configured successfully',
                content:     new OA\JsonContent(ref: '#/components/schemas/permissions_result'),
            ),
            new OA\Response(
                response:    400,
                description: 'Invalid argument supplied for access',
            ),
            new OA\Response(
                response:    404,
                description: 'Invalid endpoint group or user group',
            ),
        ]
    )]
    #[Route(
        path: '/endpoint_group/{endpointGroupName}/{userGroupId}/{access}',
        name: 'endpoint_group_user_group_access',
        requirements: ['endpointGroupName' => '.+', 'userGroupId' => '\d+', 'access' => '^(allow|deny)$'],
        methods: ['PUT']
    )]
    public function endpointGroupAddUserGroupAccessAction(
        ServerRequestInterface $request,
        string $endpointGroupName,
        int $userGroupId,
        string $access,
    ): ResponseInterface {
        try {
            $this->endpointGroupService->associateUserGroup($endpointGroupName, $userGroupId, $access);

            return $this->responseFactory->buildEmpty(200);
        } catch (EntityNotFoundException $e) {
            return $this->responseFactory->buildJsonMessage(404, $e->getMessage());
        }
    }

    /**
     * @psalm-param string $endpointGroupId
     * @psalm-param positive-int $userGroupId
     */
    #[OA\Delete(
        path: '/endpoint_group/{endpointGroupId}/{userGroupId}',
        operationId: 'endpoint_group_user_group_access_delete',
        security: [['oauth2-user' => ['admin']]],
        tags: ['Endpoint Group User Group Access'],
        parameters: [
            new OA\Parameter(
                name:        'endpointGroupId',
                description: '',
                in:          'path',
                schema:      new OA\Schema(type: 'string', minLength: 1),
                example:     'UniqueGroupName',
            ),
            new OA\Parameter(
                name:        'userGroupId',
                description: '',
                in:          'path',
                schema:      new OA\Schema(type: 'integer', minimum: 1),
                example:     1,
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: '',
            ),
            new OA\Response(
                response:    400,
                description: 'Invalid input',
            ),
        ]
    )]
    #[Route(
        path: '/endpoint_group/{endpointGroupId}/{userGroupId}',
        name: 'endpoint_group_user_group_access_delete',
        requirements: ['endpointGroupId' => '[A-Za-z0-9_]+', 'userGroupId' => '\d+'],
        methods: ['DELETE']
    )]
    public function endpointGroupUserGroupAccessDelete(
        ServerRequestInterface $request,
        string $endpointGroupId,
        int $userGroupId,
    ): ResponseInterface {
        try {
            $this->endpointGroupService
                ->deleteEndpointGroupUserGroupAccess($endpointGroupId, $userGroupId);

            return $this->responseFactory->buildJson(200, 'Ok');
        } catch (EndpointGroupException | UserGroupException | EndpointGroupUserGroupAccessException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }
    }
}
