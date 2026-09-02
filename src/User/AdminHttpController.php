<?php

declare(strict_types=1);

namespace Application\Core\User;

use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Core\User\DTO\AdminCreationDTO;
use Application\Core\User\DTO\AdminDetailsSwaggerDTO;
use Application\Core\User\Exception\UserServiceException;
use Application\Core\User\Repository\UserRepository;
use Application\Core\User\Service\UserService;
use DI\Annotation\Inject;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;
use Symfony\Component\Routing\Annotation\Route;

final class AdminHttpController
{
    /** @Inject() */
    private HttpResponseFactory $responseFactory;

    /** @Inject() */
    private UserRepository $userRepository;

    /** @Inject() */
    private UserService $userService;

    #[OA\Get(
        path: "/admins",
        operationId: "admin_list",
        description: "Lists all Admins.",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["Admin"],
        responses: [
            new OA\Response(
                response:    200,
                description: "A list of all Admins",
                content:     new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/admin_details"),
                )
            ),
        ]
    )]
    #[Route(path: "/admins", name: "admin_list", methods: ["GET"])]
    public function adminListAction(ServerRequestInterface $request): ResponseInterface
    {
        $admins = $this->userRepository->findAllAdmins();
        $responseDTO = AdminDetailsSwaggerDTO::fromAdmins($admins);

        return $this->responseFactory->buildJson(200, $responseDTO);
    }

    #[OA\Get(
        path: "/admin/{adminId}",
        operationId: "admin_get",
        description: "Get Admin or User details.",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["Admin"],
        parameters: [
            new OA\Parameter(
                name:    'adminId',
                in:      'path',
                schema:  new OA\Schema(type: 'integer', minimum: 1),
                example: 1,
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: "The details of any Admin",
                content: new OA\JsonContent(ref: '#/components/schemas/admin_details'),
            ),
        ]
    )]
    #[Route(path: "/admin/{adminId}", name: "admin_get", requirements: ["adminId" => "\d+"], methods: ["GET"])]
    public function adminGetAction(int $adminId): ResponseInterface
    {
        try {
            $dto = $this->userService->adminGetDetails($adminId);
        } catch (UserServiceException $exception) {
            return $this->responseFactory->buildJson(404, 'Admin not found.');
        }

        return $this->responseFactory->buildJson(200, $dto);
    }

    #[OA\Post(
        path: "/admin",
        operationId: "admin_add",
        description: "Creates an Admin with a name, email address, and a password.",
        security: [["oauth2-user" => ["admin"]]],
        requestBody: new OA\RequestBody(
            description: "The new Admin's properties",
            required:    true,
            content:     new OA\JsonContent(ref: "#/components/schemas/admin_creation")
        ),
        tags: ["Admin"],
        responses: [
            new OA\Response(
                response: 200,
                description: "The details of the created Admin",
                content: new OA\JsonContent(ref: "#/components/schemas/admin_details")
            ),
            new OA\Response(response: 400, description: "Invalid email address or password"),
            new OA\Response(response: 409, description: "Email address in use")
        ]
    )]
    #[Route(path: "/admin", name: "admin_add", methods: ["POST"])]
    public function adminAddAction(ServerRequestInterface $request): ResponseInterface
    {
        $json = json_decode((string) $request->getBody());
        assert($json instanceof stdClass);
        $requestDTO = AdminCreationDTO::fromJson($json);

        try {
            $newAdmin = $this->userService->registerAdminFromDto($requestDTO);
            $responseDTO = AdminDetailsSwaggerDTO::fromEntity($newAdmin);

            return $this->responseFactory->buildJson(200, $responseDTO);
        } catch (UserServiceException $e) {
            return $this->responseFactory->buildJsonMessage(400, $e->getMessage());
        }
    }
}
