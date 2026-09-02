<?php

namespace Application\Core\Util\Swagger;

use Application\Common\SymfonyRouting\RouteCollectorInterface;
use DI\Annotation\Inject;
use Doctrine\Common\Annotations\AnnotationReader;
use OpenApi\Annotations\Operation;
use OpenApi\Generator;
use ReflectionMethod;
use Symfony\Component\Routing\Annotation\Route;

final class SwaggerService
{
    /** @Inject("application.dispatcher.route_collector") */
    private RouteCollectorInterface $routeCollector;

    /**
     * @return string[]
     */
    public function listEndpointsByRole(string $role): array
    {
        $result = [];

        foreach ($this->routeCollector->collectRoutes()->all() as $action) {
            $controllerClassName = $action->getDefault('_controllerClass');
            assert(is_string($controllerClassName));
            $controllerMethodName = $action->getDefault('_controllerMethod');
            assert(is_string($controllerMethodName));

            $reflection = new ReflectionMethod($controllerClassName, $controllerMethodName);

            foreach ($this->routesOnReflectedMethods($reflection) as $route) {
                foreach ($this->operationsOnReflectedMethod($reflection) as $operation) {
                    if ($operation->security === Generator::UNDEFINED) {
                        $result[] = $this->formatOutput($route);
                        continue;
                    }

                    foreach ($operation->security as $securityInfo) {
                        foreach ($securityInfo as $schema) {
                            if (in_array($role, $schema, true)) {
                                $result[] = $this->formatOutput($route);
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    private function formatOutput(Route $route): string
    {
        return sprintf('%1$-8s %2$s', implode(',', $route->getMethods()), $route->getPath());
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
     * @return Operation[]
     */
    private function operationsOnReflectedMethod(ReflectionMethod $reflectionMethod): array
    {
        /** @var Operation[] $operations */
        $operations = [];

        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();
            if ($attributeInstance instanceof Operation) {
                $operations[] = $attributeInstance;
            }
        }

        $annotationReader = new AnnotationReader();
        foreach ($annotationReader->getMethodAnnotations($reflectionMethod) as $annotation) {
            if ($annotation instanceof Operation) {
                $operations[] = $annotation;
            }
        }

        return $operations;
    }
}
