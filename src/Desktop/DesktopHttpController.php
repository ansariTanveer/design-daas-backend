<?php

declare(strict_types=1);

namespace Application\Core\Desktop;

use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Core\Desktop\DTO\DesktopDetailsDTO;
use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\Desktop\Exception\DesktopGroupException;
use Application\Core\User\DTO\UserGroupDetailsDTO;
use Application\Core\User\Exception\UserGroupServiceException;
use Application\Core\User\Service\UserGroupService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class DesktopHttpController
{
    /** @Inject() */
    private HttpResponseFactory $responseFactory;

    /** @Inject() */
    private DesktopService $desktopService;

    /** @Inject() */
    private UserGroupService $userGroupService;

    #[OA\Post(
        path: "/desktop-group",
        operationId: "desktop_group_create",
        description: "Creates a Desktop Group with a description.",
        security: [["oauth2-user" => ["admin"]]],
        requestBody: new OA\RequestBody(
            description: "The description that is added to the new Desktop Group",
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/desktop_group_details")
        ),
        tags: ["Desktop Group"],
        responses: [
            new OA\Response(
                response: 200,
                description: "The new Desktop Group's details",
                content: new OA\JsonContent(ref: "#/components/schemas/desktop_group_details")
            )
        ]
    )]
    #[Route(path: "/desktop-group", name: "desktop_group_create", methods: ["POST"])]
    public function desktopGroupCreateAction(ServerRequestInterface $request): ResponseInterface
    {
        $requestDTO = DesktopGroupDetailsDTO::fromRequest($request);

        $desktopGroup = $this->desktopService->addDesktopGroup($requestDTO);

        $responseDTO = DesktopGroupDetailsDTO::fromEntity($desktopGroup);

        return $this->responseFactory->buildJson(200, $responseDTO);
    }

    #[OA\Post(
        path: "/desktop_group/{desktopGroupId}/user_group/{userGroupId}",
        operationId: "desktop_group_add_user_group",
        description: "Associates a User Group with a Desktop Group.",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["Desktop Group"],
        parameters: [
            new OA\Parameter(
                name: "desktopGroupId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1, example: 1),
            ),
            new OA\Parameter(
                name: "userGroupId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", minimum: 1, example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "A list of all User Groups associated with the modified Desktop Group.",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/user_group_details")
                )
            ),
            new OA\Response(response: 404, description: "Desktop Group or User Group not found."),
            new OA\Response(response: 409, description: "Desktop Group is already associated with User Group.")
        ]
    )]
    #[Route(
        path: "/desktop_group/{desktopGroupId}/user_group/{userGroupId}",
        name: "desktop_group_add_user_group",
        requirements: ["desktopGroupId" => "\d+", "userGroupId" => "\d+"],
        methods: ["POST"]
    )]
    public function desktopGroupAddUserGroupAction(
        int $desktopGroupId,
        int $userGroupId,
        ServerRequestInterface $request
    ): ResponseInterface {
        try {
            $desktopGroup = $this->desktopService->getGroup($desktopGroupId);
            $userGroup = $this->userGroupService->getGroup($userGroupId);
        } catch (DesktopGroupException | UserGroupServiceException $e) {
            return $this->responseFactory->buildJsonMessage(404, $e->getMessage());
        }

        try {
            $desktopGroup->addUserGroup($userGroup);
        } catch (DesktopGroupException $e) {
            return $this->responseFactory->buildJsonMessage(409, $e->getMessage());
        }

        $associatedUserGroups = $desktopGroup->userGroups();
        return $this->responseFactory->buildJson(200, UserGroupDetailsDTO::fromGroups($associatedUserGroups));
    }

    #[OA\Post(
        path: "/desktop",
        operationId: "desktop_add",
        description: "Creates a Desktop with a description.",
        security: [["oauth2-user" => ["admin"]]],
        requestBody: new OA\RequestBody(
            description: "The description that is added to the new Desktop",
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/desktop_details")
        ),
        tags: ["Desktop"],
        responses: [
            new OA\Response(
                response: 200,
                description: "The new Desktop's details",
                content: new OA\JsonContent(ref: "#/components/schemas/desktop_details")
            )
        ]
    )]
    #[Route(path: "/desktop", name: "desktop_add", methods: ["POST"])]
    public function desktopAddAction(ServerRequestInterface $request): ResponseInterface
    {
        $requestDTO = DesktopDetailsDTO::fromRequest($request);
        $desktop = $this->desktopService->createDesktop($requestDTO);

        $responseDTO = DesktopDetailsDTO::fromEntity($desktop);
        return $this->responseFactory->buildJson(200, $responseDTO);
    }

    /**@psalm-param positive-int $desktopId*/
    #[OA\Get(
        path: '/desktop/{desktopId}',
        operationId: 'desktop_get',
        security: [['oauth2-user' => ['admin']]],
        tags: ['Desktop'],
        parameters: [
            new OA\Parameter(
                name: 'desktopId',
                in: 'path',
                schema: new OA\Schema(type: 'integer', minimum: 1),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Details of desktop',
                content: new OA\JsonContent(ref: '#/components/schemas/desktop_details')
            ),
            new OA\Response(response: 404, description: "Desktop not found."),
        ]
    )]
    #[Route(path: '/desktop/{desktopId}', name: 'desktop_get', requirements: ['desktopId' => '\d+'], methods: ['GET'])]
    public function desktopGetAction(
        int $desktopId
    ): ResponseInterface {
        try {
            assert($desktopId > 0);
            return $this->responseFactory->buildJson(
                200,
                $this->desktopService->getDesktop($desktopId)
            );
        } catch (DesktopException $e) {
            return $this->responseFactory->buildJsonMessage(404, $e->getMessage());
        }
    }

    #[OA\Get(
        path: "/desktops",
        operationId: "desktop_list",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["Desktop"],
        responses: [
            new OA\Response(
                response: 200,
                description: "List of desktops",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/desktop_details")
                )
            ),
        ]
    )]
    #[Route(
        path: "/desktops",
        name: "desktop_list",
        methods: ["GET"]
    )]
    public function desktopListAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->buildJson(
            200,
            DesktopDetailsDTO::fromEntities($this->desktopService->listDesktop())
        );
    }

    /** @psalm-param positive-int $desktopGroupId */
    #[OA\Get(
        path: '/desktop_group/{desktopGroupId}',
        operationId: 'desktop_group_get',
        security: [['oauth2-user' => ['admin']]],
        tags: ['Desktop Group'],
        parameters: [
            new OA\Parameter(
                name: 'desktopGroupId',
                in: 'path',
                schema: new OA\Schema(type: 'integer', minimum: 1),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Details of desktop group',
                content: new OA\JsonContent(ref: '#/components/schemas/desktop_group_details')
            ),
            new OA\Response(response: 404, description: "Desktop group not found."),
        ]
    )]
    #[Route(
        path: '/desktop_group/{desktopGroupId}',
        name: 'desktop_group_get',
        requirements: ['desktopGroupId' => '\d+'],
        methods: ['GET']
    )]
    public function desktopGroupGetAction(
        int $desktopGroupId
    ): ResponseInterface {
        try {
            return $this->responseFactory->buildJson(
                200,
                $this->desktopService->getDesktopGroupDTO($desktopGroupId)
            );
        } catch (DesktopGroupException $e) {
            return $this->responseFactory->buildJsonMessage(404, $e->getMessage());
        }
    }
}
