<?php

namespace Application\Core\Permissions\Controller;

use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Core\Permissions\DTO\PermissionRequestDTO;
use Application\Core\Permissions\Exception\PermissionsException;
use Application\Core\Permissions\Service\PermissionCalculationService;
use Application\Core\User\Exception\BaseUserException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final readonly class PermissionsHttpController
{
    public function __construct(
        private PermissionCalculationService $permissionCalculationService,
        private HttpResponseFactory $responseFactory,
    ) {
    }

    /**
     * @psalm-param non-empty-string $functionName
     * @psalm-param positive-int $userId
     */
    #[OA\Get(
        path: '/permissions/{functionName}/{userId}',
        operationId: 'permissions_get',
        security: [['oauth2-user' => ['admin']]],
        tags: ['Permissions'],
        parameters: [
            new OA\Parameter(
                name: 'functionName',
                description: '',
                in: 'path',
                schema: new OA\Schema(type: 'string', minLength: 1),
                example: 'fnSample'
            ),
            new OA\Parameter(
                name: 'userId',
                description: '',
                in: 'path',
                schema: new OA\Schema(type: 'integer', minimum: 1),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: '',
                content: new OA\JsonContent(ref: '#/components/schemas/permissions_result')
            ),
        ]
    )]
    #[Route(
        path: '/permissions/{functionName}/{userId}',
        name: 'permissions_get',
        requirements: ['functionName' => '[A-Za-z0-9_]+', 'userId' => '\d+'],
        methods: ['GET']
    )]
    public function userPermissionAction(
        ServerRequestInterface $request,
        string $functionName,
        int $userId
    ): ResponseInterface {
        try {
            $result = $this->permissionCalculationService->getPermissionAsDto($functionName, $userId);

            return $this->responseFactory->buildJson(200, $result);
        } catch (PermissionsException | BaseUserException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }
    }

    #[OA\Post(
        path: '/permissions_info',
        operationId: 'permissions_info',
        security: [['oauth2-user' => ['admin']]],
        requestBody: new OA\RequestBody(
            description: "Permission request",
            required:    true,
            content:     new OA\JsonContent(ref: "#/components/schemas/permissions_request")
        ),
        tags: ['Permissions'],
        responses: [
            new OA\Response(
                response: 200,
                description: '',
                content: new OA\JsonContent(ref: '#/components/schemas/permissions_result')
            ),
        ]
    )]
    #[Route(
        path: '/permissions_info',
        name: 'permissions_info',
        methods: ['POST']
    )]
    public function permissionInfoAction(
        ServerRequestInterface $request,
    ): ResponseInterface {
        try {
            $dto = PermissionRequestDTO::fromRequest($request);

            $result = $this->permissionCalculationService->getPermissionsInfo($request, $dto);

            return $this->responseFactory->buildJson(200, $result);
        } catch (PermissionsException | BaseUserException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }
    }
}
