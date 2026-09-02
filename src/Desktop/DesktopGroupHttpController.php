<?php

declare(strict_types=1);

namespace Application\Core\Desktop;

use Application\Common\HttpResponse\HttpResponseFactory;
use DI\Annotation\Inject;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

final class DesktopGroupHttpController
{
    /**
     * @Inject()
     */
    private HttpResponseFactory $responseFactory;

    /**
     * @Inject()
     */
    private DesktopGroupService $desktopGroupService;

    #[OA\Get(
        path: "/desktop_groups",
        operationId: "desktop_group_list",
        security: [["oauth2-user" => ["admin"]]],
        tags: ["Desktop Group"],
        responses: [
            new OA\Response(
                response: 200,
                description: "",
                content: new OA\JsonContent(
                    type: "array",
                    items: new OA\Items(ref: "#/components/schemas/desktop_group_details")
                )
            ),
        ]
    )]
    #[Route(
        path: "/desktop_groups",
        name: "desktop_group_list",
        methods: ["GET"]
    )]
    public function desktopGroupListAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->buildJson(
            200,
            $this->desktopGroupService->listDesktopGroups()
        );
    }
}
