<?php

declare(strict_types=1);

namespace Application\Test\Integration;

use Application\Common\SymfonyRouting\RouteCollector;
use Application\Common\SymfonyRouting\RouteCollectorInterface;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\Common\Annotations\AnnotationReader;
use OpenApi\Annotations as OA;
use OpenApi\Attributes as OAT;
use OpenApi\Generator;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
// Despite the name, this covers both Annotations and Attributes. (Bw compatibility)
use Symfony\Component\Routing\Annotation\Route;

/**
 * @psalm-type DataCollectedBySetUp = object{
 *     clearName: string,
 *     route: Route,
 *     operation: OA\Operation,
 *     reflection: ReflectionMethod,
 * }
 */
final class EndpointConfigurationTest extends TestCase
{
    // Do not validate these actions
    private const ACTIONS_TO_SKIP = [
        'userTokenAction',
        'versionAction',
        'swaggerUiIndexRedirectAction',
        'swaggerUiIndexAction',
        'swaggerUiAction',
    ];

    // Whitelist your action here if it should be available without restrictions
    private const ACTIONS_WITH_NO_SECURITY = [
        'Application\Core\User\Controller\UserHttpController::userCreateAction',
        'Application\Core\User\Controller\UserHttpController::userValidateEmailAction',
        'Application\Core\Util\Swagger\SwaggerHttpController::swaggerEndpointsByRoleAction'
    ];

    /** @psalm-var DataCollectedBySetUp[] */
    private array $endpointInfos = [];

    /**
     * @throws DependencyException
     * @throws NotFoundException
     * @throws ReflectionException
     */
    protected function setUp(): void
    {
        parent::setUp();
        $application = TestApplicationFactory::http();

        $routeCollector = $application->container()->get('application.dispatcher.route_collector');
        assert($routeCollector instanceof RouteCollectorInterface);

        foreach ($routeCollector->collectRoutes()->all() as $action) {
            $controllerClassName = $action->getDefault('_controllerClass');
            assert(is_string($controllerClassName));
            $controllerMethodName = $action->getDefault('_controllerMethod');
            assert(is_string($controllerMethodName));

            if (in_array($controllerMethodName, self::ACTIONS_TO_SKIP, true)) {
                continue;
            }

            $reflection = new ReflectionMethod($controllerClassName, $controllerMethodName);

            $clearName = sprintf('%1$s::%2$s', $controllerClassName, $controllerMethodName);

            $routesInAction = $this->routesOnReflectedMethods($reflection);
            self::assertCount(
                1,
                $routesInAction,
                sprintf('%1$s: Invalid amount of #[route attributes/annotations', $clearName)
            );
            $route = $routesInAction[0];

            $operationsInAction = $this->operationsOnReflectedMethod($reflection);
            self::assertCount(
                1,
                $operationsInAction,
                sprintf('%1$s: Invalid amount of operations (GET/POST/etc.) attributes/annotations', $clearName)
            );
            $operation = $operationsInAction[0];

            $this->endpointInfos[] = (object)[
                'clearName' => $clearName,
                'reflection' => $reflection,
                'route' => $route,
                'operation' => $operation,
            ];
        }
    }

    public function testOperationAndRouteSettingsMatch(): void
    {
        foreach ($this->endpointInfos as $endpointInfo) {
            /** @psalm-var DataCollectedBySetUp $endpointInfo */
            self::assertEquals(
                $endpointInfo->operation->path,
                $endpointInfo->route->getPath(),
                sprintf(
                    '%1$s: "path" values do not match',
                    $endpointInfo->clearName
                )
            );

            self::assertEquals(
                $endpointInfo->operation->operationId,
                $endpointInfo->route->getName(),
                sprintf(
                    '%1$s: "name" values do not match',
                    $endpointInfo->clearName
                )
            );

            self::assertCount(
                1,
                $endpointInfo->route->getMethods(),
                sprintf(
                    '%1$s: Has an invalid amount of methods in route',
                    $endpointInfo->clearName
                )
            );

            $methodKeywordThatShouldBeInRoute = match ($endpointInfo->operation::class) {
                OA\Get::class, OAT\Get::class => 'GET',
                OA\Patch::class, OAT\Patch::class => 'PATCH',
                OA\Post::class, OAT\Post::class => 'POST',
                OA\Delete::class, OAT\Delete::class => 'DELETE',
                OA\Put::class, OAT\Put::class => 'PUT',
                OA\Options::class, OAT\Options::class => 'OPTIONS',
                default => null
            };
            assert(!is_null($methodKeywordThatShouldBeInRoute));
            self::assertTrue(
                in_array($methodKeywordThatShouldBeInRoute, $endpointInfo->route->getMethods(), true),
                sprintf(
                    '%1$s: Should have "%2$s" keyword as route method',
                    $endpointInfo->clearName,
                    $methodKeywordThatShouldBeInRoute
                )
            );
        }
    }

    public function testSecuritySet(): void
    {
        foreach ($this->endpointInfos as $endpointInfo) {
            /** @psalm-var DataCollectedBySetUp $endpointInfo */
            if (in_array($endpointInfo->clearName, self::ACTIONS_WITH_NO_SECURITY, true)) {
                continue;
            }

            self::assertNotEquals(
                Generator::UNDEFINED,
                $endpointInfo->operation->security,
                sprintf(
                    '%1$s: Undeclared public endpoint (Whitelist in EndpointConfigurationTest.php if deliberate)',
                    $endpointInfo->clearName
                )
            );

            $oauth2Found = false;
            foreach ($endpointInfo->operation->security as $securityInfo) {
                /** @var array<string, string[]> $securityInfo */
                if (array_key_exists('oauth2-user', $securityInfo)) {
                    $oauth2Found = true;
                    break;
                }
            }

            self::assertTrue(
                $oauth2Found,
                sprintf(
                    '%1$s: Oauth2 Security (i.E. [["oauth2-user" => ["admin"]]] ) not set',
                    $endpointInfo->clearName
                )
            );
        }
    }

    public function testParametersMatch(): void
    {
        foreach ($this->endpointInfos as $endpointInfo) {
            /** @psalm-var DataCollectedBySetUp $endpointInfo */

            $parametersInOperationsPath = $this->parameterInPath($endpointInfo->operation->path);
            foreach ($parametersInOperationsPath as $parameterInOperationPath) {
                self::assertTrue(
                    $this->operationHasParameter($endpointInfo->operation, $parameterInOperationPath),
                    sprintf(
                        '%1$s: Missing OA\Parameter for "%2$s" (or not set to in=path)',
                        $endpointInfo->clearName,
                        $parameterInOperationPath
                    )
                );
            }

            $parametersInRoutePath = $this->parameterInPath($endpointInfo->route->getPath());
            self::assertEquals(
                $parametersInRoutePath,
                $parametersInOperationsPath,
                sprintf(
                    '%1$s: Missing parameter(s) on "path" of #[Route',
                    $endpointInfo->clearName
                )
            );

            $parametersInRouteRequirements = array_keys($endpointInfo->route->getRequirements());
            self::assertEquals(
                $parametersInOperationsPath,
                $parametersInRouteRequirements,
                sprintf(
                    '%1$s: Missing parameter(s) in "requirements" of #[Route',
                    $endpointInfo->clearName
                )
            );

            $parametersInActionDeclaration = $this->buildInParametersOnReflectedMethod($endpointInfo->reflection);
            self::assertEquals(
                $parametersInOperationsPath,
                $parametersInActionDeclaration,
                sprintf(
                    '%1$s: Missing variable in function declaration of action',
                    $endpointInfo->clearName
                )
            );
        }
    }

    /**
     * @return OA\Operation[]
     */
    private function operationsOnReflectedMethod(ReflectionMethod $reflectionMethod): array
    {
        /** @var OA\Operation[] $operations */
        $operations = [];

        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();
            if ($attributeInstance instanceof OA\Operation) {
                $operations[] = $attributeInstance;
            }
        }

        $annotationReader = new AnnotationReader();
        foreach ($annotationReader->getMethodAnnotations($reflectionMethod) as $annotation) {
            if ($annotation instanceof OA\Operation) {
                $operations[] = $annotation;
            }
        }

        return $operations;
    }

    /**
     * @return Route[]
     */
    private function routesOnReflectedMethods(ReflectionMethod $reflectionMethod): array
    {
        /** @var Route[] $routes */
        $routes = [];

        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();
            if ($attributeInstance instanceof Route) {
                $routes[] = $attributeInstance;
            }
        }

        $annotationReader = new AnnotationReader();
        foreach ($annotationReader->getMethodAnnotations($reflectionMethod) as $annotation) {
            if ($annotation instanceof Route) {
                $routes[] = $annotation;
            }
        }

        return $routes;
    }


    /**
     * @return string[]
     */
    private function parameterInPath(string $path): array
    {
        $result = preg_match_all(
            '/{(?<param>[^}]+)}/m',
            $path,
            $matches,
            PREG_PATTERN_ORDER
        );
        assert(is_int($result));

        return $matches['param'];
    }

    /**
     * @return string[]
     */
    private function buildInParametersOnReflectedMethod(ReflectionMethod $reflectionMethod): array
    {
        /** @var string[] $names */
        $names = [];

        foreach ($reflectionMethod->getParameters() as $parameter) {
            $parameterType = $parameter->getType();
            if ($parameterType instanceof ReflectionNamedType && $parameterType->isBuiltin()) {
                $names[] = $parameter->getName();
            }
        }

        return $names;
    }

    private function operationHasParameter(OA\Operation $operation, string $parameterName): bool
    {
        if (!is_array($operation->parameters)) {
            return false;
        }

        foreach ($operation->parameters as $parameter) {
            if ($parameter->in === 'path' && $parameter->name === $parameterName) {
                return true;
            }
        }

        return false;
    }
}
