<?php

namespace Application\Core\Util\Swagger;

use Application\Common\HttpBaseUrl\BaseUrlResolverInterface;
use Application\Common\HttpResponse\HttpFileResponseFactory;
use Application\Common\HttpResponse\HttpResponseFactory;
use Application\Common\SymfonyRouting\RouteCollectorInterface;
use DI\Annotation\Inject;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Routing\Annotation\Route;

#[OA\Info(version: 'API_VERSION', title: 'DESIGN')]
#[OA\Server(url: 'REQUEST_BASE_URL')]
#[OA\SecurityScheme(
    securityScheme: 'oauth2-user',
    type: 'oauth2',
    flows: [
        new OA\Flow(
            null,
            'REQUEST_BASE_URL/oauth2/user/token',
            null,
            'password',
            [
                'user' => 'Allows access to user endpoints',
                'admin' => 'Allows access to admin endpoints',
            ],
        ),
    ]
)]
final class SwaggerHttpController
{
    /** @Inject("application.runtime.root_dir") */
    private string $applicationDir;

    /** @Inject() */
    private HttpResponseFactory $responseFactory;

    /** @Inject() */
    private HttpFileResponseFactory $fileResponseFactory;

    /** @Inject() */
    private SwaggerService $swaggerService;

    /** @Inject() */
    private SwaggerDefinitionCache $definitionCache;

    /** @Inject() */
    private BaseUrlResolverInterface $baseUrlResolver;

    /**
     * @Inject("application.runtime.version_details")
     * @var array{application_name: string, application_version: string}
     */
    private array $versionDetails;

    #[Route(path: '/swagger', name: 'swagger_ui_index_redirect', methods: ['GET'])]
    public function swaggerUiIndexRedirectAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->buildLocalRedirect('/swagger/');
    }

    #[Route(path: '/swagger/', name: 'swagger_ui_index', methods: ['GET'])]
    #[OA\Get(
        path: '/swagger',
        operationId: 'swagger',
        tags: ['swagger'],
        responses: [
            new OA\Response(null, 200, 'Swagger UI'),
        ]
    )]
    public function swaggerUiIndexAction(ServerRequestInterface $request): ResponseInterface
    {
        return $this->swaggerUiAction($request, 'index.html');
    }

    #[Route(path: '/swagger/{file}', name: 'swagger_ui', methods: ['GET'])]
    public function swaggerUiAction(ServerRequestInterface $request, string $file): ResponseInterface
    {
        if ($file === 'swagger.json') {
            assert(array_key_exists('application_version', $this->versionDetails));
            assert(is_scalar($this->versionDetails['application_version']));

            return $this->responseFactory->buildJson(
                200,
                $this->definitionCache->loadJson(
                    (string)$this->versionDetails['application_version'],
                    $this->baseUrlResolver->baseUrl(),
                ),
            );
        }

        $realFile = $this->applicationDir . '/assets/swagger-ui/' . $file;
        if (!file_exists($realFile)) {
            return $this->responseFactory->buildEmpty(404);
        }

        return $this->fileResponseFactory->buildFromFile($request, $realFile);
    }

    #[OA\Get(
        path: '/swagger-by-role/{role}',
        operationId: 'swagger_by_role',
        tags: ['Admin'],
        parameters: [
            new OA\Parameter(
                name:    'role',
                in:      'path',
                schema: new OA\Schema(type: 'string', minLength: 1),
            ),
        ],
        responses: [
            new OA\Response(
                response:    200,
                description: 'List of endpoints accessible by the role',
                content: new OA\JsonContent(),
            ),
        ]
    )]
    #[Route(
        path: '/swagger-by-role/{role}',
        name: 'swagger_by_role',
        requirements: ['role' => '[A-Za-z0-9_]+'],
        methods: ['GET']
    )]
    public function swaggerEndpointsByRoleAction(ServerRequestInterface $request, string $role): ResponseInterface
    {
        return $this->responseFactory->buildJson(200, $this->swaggerService->listEndpointsByRole($role));
    }
}
