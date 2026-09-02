<?php

declare(strict_types=1);

namespace Application\Core\OAuth2;

use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Core\OAuth2\DTO\UserSessionDetailsSwaggerDTO;
use DI\Annotation\Inject;
use League\OAuth2\Server\Exception\OAuthServerException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

class OAuth2HttpController
{
    /** @Inject() */
    private HttpResponseFactory $responseFactory;

    /** @Inject() */
    private OAuth2AuthorizationServer $authServer;

    #[Route(path: "/oauth2/user/token", name: "oauth2_user_token", methods: ["POST"])]
    public function userTokenAction(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $response = $this->authServer->respondToAccessTokenRequest(
                $request,
                $this->responseFactory->buildEmpty(500)
            );
            return $response;
        } catch (OAuthServerException $exception) {
            return $exception->generateHttpResponse($this->responseFactory->buildEmpty(500));
        }
    }

    #[OA\Get(
        path: "/oauth2/user/session",
        operationId: "oauth2_user_session_details",
        description: "Returns basic information about a logged-in user as well as their OAuth scopes.",
        security: [["oauth2-user" => ["user", "admin"]]],
        tags: ["OAuth2"],
        responses: [
            new OA\Response(
                response: 200,
                description: "Session details",
                content: new OA\JsonContent(ref: "#/components/schemas/user_session_details")
            ),
            new OA\Response(response: 401, description: "Unauthorized"),
        ]
    )]
    #[Route(path: "/oauth2/user/session", name: "oauth2_user_session_details", methods: ["GET"])]
    public function userSessionDetailsAction(ServerRequestInterface $request): ResponseInterface
    {
        $user = OAuth2AuthorizationValidator::userOfRequest($request);

        $attributes = $request->getAttributes();
        $scopes = $attributes["oauth_scopes"];

        $responseDTO = UserSessionDetailsSwaggerDTO::fromUserAndScopes($user, $scopes);

        return $this->responseFactory->buildJson(200, $responseDTO);
    }

    #[OA\Get(
        path: "/ping/user",
        operationId: "ping_user",
        description: "A debugging endpoint that returns different status codes depending on who is logged in.",
        security: [["oauth2-user" => ["user"]]],
        tags: ["OAuth2"],
        responses: [
            new OA\Response(response: 200, description: "Request was sent by a User"),
            new OA\Response(response: 401, description: "Request was sent without being logged in"),
            new OA\Response(response: 403, description: "Request was sent by an Admin"),
        ]
    )]
    #[Route(path: "/ping/user", name: "ping_user", methods: ["GET"])]
    public function pingUser(ServerRequestInterface $request): ResponseInterface
    {
        // test endpoint to test user restricted endpoint
        return $this->responseFactory->buildEmpty(200);
    }

    #[OA\Get(
        path: "/ping/admin",
        operationId: "ping_admin",
        description: "A debugging endpoint that returns different status codes depending on who is logged in.",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["OAuth2"],
        responses: [
            new OA\Response(response: 200, description: "Request was sent by a User"),
            new OA\Response(response: 401, description: "Request was sent without being logged in"),
            new OA\Response(response: 403, description: "Request was sent by an Admin"),
        ]
    )]
    #[Route(path: "/ping/admin", name: "ping_admin", methods: ["GET"])]
    public function pingAdmin(ServerRequestInterface $request): ResponseInterface
    {
        // test endpoint to test user restricted endpoint
        return $this->responseFactory->buildEmpty(200);
    }
}
