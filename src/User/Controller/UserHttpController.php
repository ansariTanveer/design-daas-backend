<?php

namespace Application\Core\User\Controller;

use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\OAuth2\OAuth2AuthorizationValidator;
use Application\Core\Permissions\DTO\PermissionResultDTO;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\User\DTO\UserCreationDTO;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Core\User\DTO\UserEmailVerificationDTO;
use Application\Core\User\Enum\UserGetDetailsResult;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Exception\InvalidPasswordException;
use Application\Core\User\Exception\UserGroupException;
use Application\Core\User\Exception\UserServiceException;
use Application\Core\User\Service\UserService;
use Application\Core\Util\Mail\MailLanguageEnum;
use DI\Annotation\Inject;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

class UserHttpController
{
    /** @Inject() */
    private HttpResponseFactory $responseFactory;

    /** @Inject() */
    private UserService $userService;

    /** @Inject() */
    private EntityManagerInterface $em;

    #[OA\Post(
        path: "/user",
        operationId: "user_create",
        description: "Creates a User with all required properties.",
        requestBody: new OA\RequestBody(
            description: "The new User's properties",
            required:    true,
            content:     new OA\JsonContent(ref: "#/components/schemas/user_creation")
        ),
        tags: ["User"],
        responses: [
            new OA\Response(
                response:    200,
                description: "The details of the created User",
                content:     new OA\JsonContent(ref: "#/components/schemas/user_details")
            ),
            new OA\Response(response: 400, description: "Invalid input"),
        ]
    )]
    #[Route(path: "/user", name: "user_create", methods: "POST")]
    public function userCreateAction(ServerRequestInterface $request): ResponseInterface
    {
        $requestDTO = UserCreationDTO::fromRequest($request);

        try {
            $newUser = $this->userService->registerUser($requestDTO);
        } catch (UserServiceException | BaseUserException | InvalidPasswordException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }

        $this->em->flush();
        $this->em->clear();

        $responseDTO = UserDetailsSwaggerDTO::fromUser($newUser);
        return $this->responseFactory->buildJson(200, $responseDTO);
    }

    #[OA\Patch(
        path: "/user/{userId}",
        operationId: "user_update",
        description: "Updates a User's name and/or email address.",
        security: [["oauth2-user" => ["admin"]]],
        requestBody: new OA\RequestBody(
            description: "The User's updated data",
            required:    true,
            content:     new OA\JsonContent(ref: "#/components/schemas/user_details")
        ),
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name:        "userId",
                description: "The ID of the User to edit",
                in:          "path",
                required:    true,
                schema:      new OA\Schema(type: "integer", minimum: 1, example: 1),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "User updated"),
            new OA\Response(response: 400, description: "Invalid input")
        ]
    )]
    #[Route(path: "/user/{userId}", name: "user_update", requirements: ["userId" => "\d+"], methods: "PATCH")]
    public function userUpdateAction(int $userId, ServerRequestInterface $request): ResponseInterface
    {
        $requestDTO = UserDetailsSwaggerDTO::fromRequest($request);

        try {
            $this->userService->updateUser($userId, $requestDTO);
        } catch (UserServiceException | InvalidPasswordException | BaseUserException | UserGroupException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }

        return $this->responseFactory->buildJsonMessage(200, "User updated");
    }

    #[OA\Post(
        path: "/user/{userId}/disable",
        operationId: "user_disable",
        description: "Disables a User.",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name:        "userId",
                description: "The ID of the User to disable",
                in:          "path",
                required:    true,
                schema:      new OA\Schema(type: "integer", minimum: 1, example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: "The details of the affected User",
                content:     new OA\JsonContent(ref: "#/components/schemas/user_details")
            ),
            new OA\Response(response: 400, description: "Invalid input")
        ]
    )]
    #[Route(path: "/user/{userId}/disable", name: "user_disable", requirements: ["userId" => "\d+"], methods: ["POST"])]
    public function userDisableAction(int $userId): ResponseInterface
    {
        try {
            $user = $this->userService->disableUser($userId);
            $userDTO = UserDetailsSwaggerDTO::fromUser($user);
            return $this->responseFactory->buildJson(200, $userDTO);
        } catch (UserServiceException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }
    }

    #[OA\Post(
        path: "/user/{userId}/enable",
        operationId: "user_enable",
        description: "Enables a User.",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name:        "userId",
                description: "The ID of the User to enable",
                in:          "path",
                required:    true,
                schema:      new OA\Schema(type: "integer", minimum: 1, example: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: "The details of the affected User",
                content:     new OA\JsonContent(ref: "#/components/schemas/user_details")
            ),
            new OA\Response(response: 400, description: "Invalid input")
        ]
    )]
    #[Route(path: "/user/{userId}/enable", name: "user_enable", requirements: ["userId" => "\d+"], methods: ["POST"])]
    public function userEnableAction(ServerRequestInterface $request, int $userId): ResponseInterface
    {
        try {
            $user = $this->userService->enableUser($userId);
            $userDTO = UserDetailsSwaggerDTO::fromUser($user);
            return $this->responseFactory->buildJson(200, $userDTO);
        } catch (UserServiceException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }
    }

    #[OA\Get(
        path: '/users',
        operationId: 'user_list',
        security: [['oauth2-user' => ['admin']]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(
                name:        'page',
                description: 'If set, lists users page-wise and starts at page set with this parameter. (0-indexed)',
                in:          'query',
                schema:      new OA\Schema(type: 'integer', minimum: 0),
                example:     0
            ),
            new OA\Parameter(
                name:        'per_page',
                description: 'Sets the number of users per page. Valid only together with "page" parameter',
                in:          'query',
                schema:      new OA\Schema(type: 'integer', minimum: 1),
                example:     20
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: 'A list of User details',
                content:     new OA\JsonContent(
                    type:  'array',
                    items: new OA\Items(ref: '#/components/schemas/user_details')
                )
            ),
        ]
    )]
    #[Route(path: '/users', name: 'user_list', methods: ['GET'])]
    public function userListAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        if (!array_key_exists('page', $queryParams)) {
            return $this->responseFactory->buildJson(
                200,
                $this->userService->userList()
            );
        } else {
            if (!array_key_exists('per_page', $queryParams)) {
                return $this->responseFactory->buildJsonMessage(
                    400,
                    'Missing "per_page"'
                );
            }

            $page = (int)$queryParams['page'];
            $perPage = (int)$queryParams['per_page'];

            return $this->responseFactory->buildJson(
                200,
                $this->userService->userListPage($perPage, $page)
            );
        }
    }

    #[OA\Get(
        path: '/user/{userId}',
        operationId: 'user_get',
        security: [['oauth2-user' => ['admin']]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(
                name:    'userId',
                in:      'path',
                schema:  new OA\Schema(type: 'integer', minimum: 1),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: 'Details of user',
                content: new OA\JsonContent(
                    anyOf: [
                        new OA\Schema('#/components/schemas/admin_details'),
                        new OA\Schema('#/components/schemas/user_details'),
                    ],
                ),
            ),
        ]
    )]
    #[Route(path: '/user/{userId}', name: 'user_get', requirements: ['userId' => '\d+'], methods: ['GET'])]
    public function userGetAction(
        int $userId
    ): ResponseInterface {
        $resultObject = $this->userService->userGetDetails($userId);

        return match ($resultObject->result) {
            UserGetDetailsResult::OK =>
            $this->responseFactory->buildJson(200, $resultObject->userDetailsDTO),
            UserGetDetailsResult::USER_NOT_FOUND =>
            $this->responseFactory->buildJsonMessage(404, 'User not found')
        };
    }

    #[OA\Get(
        path: '/users/access/{desktopId}',
        operationId: 'user_access',
        security: [['oauth2-user' => ['user']]],
        tags: ['User'],
        parameters: [
            new OA\Parameter(
                name:    'desktopId',
                in:      'path',
                schema:  new OA\Schema(type: 'integer', minimum: 1),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: 'Whether access is allowed or denied',
                content:     new OA\JsonContent(ref: '#/components/schemas/permissions_result')
            ),
        ]
    )]
    #[Route(
        path: '/users/access/{desktopId}',
        name: 'user_access',
        requirements: ['desktopId' => '\d+'],
        methods: ['GET']
    )]
    public function userAccessAction(
        ServerRequestInterface $request,
        int $desktopId
    ): ResponseInterface {
        $user = OAuth2AuthorizationValidator::userOfRequest($request);

        if ($user->enabled()) {
            try {
                $hasAccess = $this->userService->hasAccessToDesktop($user, $desktopId);

                if ($hasAccess === true) {
                    return $this->responseFactory->buildJson(
                        200,
                        PermissionResultDTO::fromUserAndAccessEnum($user, AccessEnum::ALLOW)
                    );
                }
            } catch (DesktopException $exception) {
                return $this->responseFactory->buildJsonMessage(404, $exception->getMessage());
            }
        }

        return $this->responseFactory->buildJson(
            200,
            PermissionResultDTO::fromUserAndAccessEnum($user, AccessEnum::DENY)
        );
    }

    #[OA\Post(
        path: "/user/validate_email",
        operationId: "user_validate_email",
        description: "Validates the email of a user",
        requestBody: new OA\RequestBody(
            description: "Validation credentials",
            required:    true,
            content:     new OA\JsonContent(ref: "#/components/schemas/user_email_verification")
        ),
        tags: ["User"],
        responses: [
            new OA\Response(response: 200, description: "Email validated successfully"),
            new OA\Response(response: 400, description: "Invalid email or registration code")
        ]
    )]
    #[Route(path: "/user/validate_email", name: "user_validate_email", methods: ["POST"])]
    public function userValidateEmailAction(ServerRequestInterface $request): ResponseInterface
    {
        $requestDTO = UserEmailVerificationDTO::fromRequest($request);

        try {
            $this->userService->validateEmailRegistrationCode($requestDTO);
        } catch (UserServiceException $e) {
            // Intentionally does not return a failure to prevent abuse
            return $this->responseFactory->buildEmpty(400);
        }
        return $this->responseFactory->buildEmpty(200);
    }

    /**
     * @param positive-int $adminId
     */
    #[OA\Delete(
        path: "/admin/{adminId}",
        operationId: "admin_delete",
        description: "Deletes an admin",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name: 'adminId',
                in: 'path',
                schema: new OA\Schema(type: 'integer', minimum: 1),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "Admin deleted successfully"),
            new OA\Response(response: 400, description: "An error occurred"),
        ]
    )]
    #[Route(
        path: "/admin/{adminId}",
        name: "admin_delete",
        requirements: ['adminId' => '\d+'],
        methods: ["DELETE"]
    )]
    public function adminDeleteAction(
        int $adminId,
        ServerRequestInterface $request
    ): ResponseInterface {
        try {
            $this->userService->deleteAdmin($adminId);
        } catch (UserServiceException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }

        return $this->responseFactory->buildJson(200, 'admin deleted');
    }

    /**
     * @param positive-int $userId
     */
    #[OA\Delete(
        path: "/user/{userId}",
        operationId: "user_delete",
        description: "Deletes an user",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name: 'userId',
                in: 'path',
                schema: new OA\Schema(type: 'integer', minimum: 1),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "User deleted successfully"),
            new OA\Response(response: 400, description: "An error occurred"),
        ]
    )]
    #[Route(
        path: "/user/{userId}",
        name: "user_delete",
        requirements: ['userId' => '\d+'],
        methods: ["DELETE"]
    )]
    public function userDeleteAction(
        int $userId,
        ServerRequestInterface $request
    ): ResponseInterface {
        try {
            $this->userService->deleteUser($userId);
        } catch (UserServiceException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }

        return $this->responseFactory->buildJson(200, 'User deleted');
    }

    /**
     * @param non-empty-string $application
     * @param non-empty-string $isoCode
     */
    #[OA\Post(
        path: "/user/request-application/{application}/{isoCode}",
        operationId: "user_request_application",
        description: "Requests access to an application",
        security: [['oauth2-user' => ['user']]],
        tags: ["User"],
        parameters: [
            new OA\Parameter(
                name: "application",
                description: "Name of the application to request",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", maxLength: 255, minLength: 1),
            ),
            new OA\Parameter(
                name: "isoCode",
                description: "Language used for the request",
                in: "path",
                required: true,
                schema: new OA\Schema(type: "string", enum: MailLanguageEnum::class),
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: "A request has been send"),
        ]
    )]
    #[Route(
        path: "/user/request-application/{application}/{isoCode}",
        name: "user_request_application",
        requirements: ['application' => '.+', 'isoCode' => '[a-z]{3}'],
        methods: ["POST"]
    )]
    public function userRequestApplication(
        ServerRequestInterface $request,
        string $application,
        string $isoCode
    ): ResponseInterface {
        $mailLanguage = MailLanguageEnum::from($isoCode);

        $user = OAuth2AuthorizationValidator::userOfRequest($request);

        $this->userService->requestApplication($user, $application, $mailLanguage);

        return $this->responseFactory->buildEmpty(200);
    }
}
