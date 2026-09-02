<?php

namespace Application\Core\User\Controller;

use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Core\User\DTO\UserGroupCreationDTO;
use Application\Core\User\DTO\UserGroupDetailsDTO;
use Application\Core\User\DTO\UserGroupUpdateDTO;
use Application\Core\User\Enum\AssociateDesktopGroupResult;
use Application\Core\User\Exception\UserGroupServiceException;
use Application\Core\User\Service\UserGroupService;
use DI\Annotation\Inject;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserGroupHttpController
{
    /** @Inject() */
    private HttpResponseFactory $responseFactory;

    /** @Inject() */
    private UserGroupService $userGroupService;

    /** @Inject() */
    private EntityManagerInterface $em;

    #[OA\Post(
        path: "/user_group",
        operationId: "user_group_create",
        description: "Creates a new User Group.",
        security: [['oauth2-user' => ['admin']]],
        requestBody: new OA\RequestBody(
            description: "The User Group data",
            required: true,
            content: new OA\JsonContent(ref: "#/components/schemas/user_group_creation")
        ),
        tags: ["User"],
        responses: [
            new OA\Response(
                response: 200,
                description: "User Group created"
            ),
            new OA\Response(
                response: 400,
                description: "Invalid input"
            ),
        ]
    )]
    #[Route(path: "/user_group", name: "user_group_create", methods: ["POST"])]
    public function userGroupCreateAction(ServerRequestInterface $request): ResponseInterface
    {
        $requestDTO = UserGroupCreationDTO::fromRequest($request);

        try {
            $newGroup = $this->userGroupService->createGroup($requestDTO->description);
        } catch (UserGroupServiceException $exception) {
            return $this->responseFactory->buildJsonMessage(400, $exception->getMessage());
        }

        $this->em->flush();

        $responseDTO = UserGroupDetailsDTO::fromGroup($newGroup);
        return $this->responseFactory->buildJson(200, $responseDTO);
    }

    #[OA\Patch(
        path: "/user_group/{groupId}",
        operationId: "user_group_update",
        description: "Updates a User Group's description.",
        security: [['oauth2-user' => ['admin']]],
        requestBody: new OA\RequestBody(
            description: "The User Group's new description",
            required:    true,
            content:     new OA\JsonContent(ref: "#/components/schemas/user_group_creation")
        ),
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name:        "groupId",
                description: "The ID of the User Group to edit",
                in:          "path",
                required:    true,
                schema:      new OA\Schema(type: "integer", minimum: 1, example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: "The details of the updated User Group",
                content:     new OA\JsonContent(ref: "#/components/schemas/user_group_details")
            ),
            new OA\Response(response: 400, description: "Invalid input"),
            new OA\Response(response: 404, description: "User Group not found"),
        ]
    )]
    #[Route(
        path: "/user_group/{groupId}",
        name: "user_group_update",
        requirements: ["groupId" => "\d+"],
        methods: ["PATCH"]
    )]
    public function userGroupUpdateAction(ServerRequestInterface $request, int $groupId): ResponseInterface
    {
        $requestDTO = UserGroupUpdateDTO::fromRequest($request);

        try {
            $newGroup = $this->userGroupService->updateGroup($groupId, $requestDTO);
        } catch (UserGroupServiceException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }

        $this->em->flush();

        $responseDTO = UserGroupDetailsDTO::fromGroup($newGroup);
        return $this->responseFactory->buildJson(200, $responseDTO);
    }

    #[OA\Get(
        path: "/user_groups",
        operationId: "user_group_list",
        description: "Lists all User Groups.",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["User"],
        responses: [
            new OA\Response(
                response:    200,
                description: "A list of all User Groups",
                content:     new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/user_group_details")
                )
            ),
        ]
    )]
    #[Route(path: "/user_groups", name: "user_group_list", methods: ["GET"])]
    public function userGroupListAction(ServerRequestInterface $request): ResponseInterface
    {
        $groups = $this->userGroupService->getAllGroups();
        $groupDTOs = UserGroupDetailsDTO::fromGroups($groups);

        return $this->responseFactory->buildJson(200, $groupDTOs);
    }

    #[OA\Post(
        path: '/user_group/{userGroupId}/{desktopGroupId}',
        operationId: 'user_group_add_desktop_group',
        security: [['oauth2-user' => ['admin']]],
        tags: ['Admins'],
        parameters: [
            new OA\Parameter(
                name: "userGroupId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64", minimum: 1)
            ),
            new OA\Parameter(
                name: "desktopGroupId",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "integer", format: "int64", minimum: 1)
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'The updated list of desktop groups associated to the user group',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/desktop_group_details')
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid user or desktop group'
            ),
            new OA\Response(
                response: 409,
                description: 'User already in desktop group'
            )
        ]
    )]
    #[Route(
        path: '/user_group/{userGroupId}/{desktopGroupId}',
        name: 'user_group_add_desktop_group',
        requirements: ['userGroupId' => '\d+', 'desktopGroupId' => '\d+'],
        methods: ['POST']
    )]
    public function userGroupAddDesktopGroup(
        ServerRequestInterface $request,
        int $userGroupId,
        int $desktopGroupId
    ): ResponseInterface {
        $resultObject = $this->userGroupService->associateDesktopGroup($userGroupId, $desktopGroupId);

        return match ($resultObject->result) {
            AssociateDesktopGroupResult::OK =>
                $this->responseFactory->buildJson(
                    200,
                    $resultObject->updatedList
                ),
            AssociateDesktopGroupResult::INVALID_USER_GROUP =>
                $this->responseFactory->buildJsonMessage(
                    400,
                    'Invalid user group'
                ),
            AssociateDesktopGroupResult::INVALID_DESKTOP_GROUP =>
                $this->responseFactory->buildJsonMessage(
                    400,
                    'Invalid desktop group'
                ),
            AssociateDesktopGroupResult::DESKTOP_GROUP_ALREADY_IN_USER_GROUP =>
                $this->responseFactory->buildJsonMessage(
                    409,
                    'Desktop group already in user group'
                ),
        };
    }
}
