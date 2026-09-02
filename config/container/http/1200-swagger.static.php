<?php

declare(strict_types=1);

use Application\Core\Util\Swagger\SwaggerDefinitionCache;
use Application\Core\Util\Swagger\SwaggerSchemaMiddleware;
use Psr\Container\ContainerInterface;

return [
    'application.dispatcher.post_route_middleware_queue' => \DI\decorate(
        function (array $previous, ContainerInterface $container): array {
            return array_merge(
                $previous,
                [
                    $container->get(SwaggerSchemaMiddleware::class),
                ]
            );
        }
    ),
    SwaggerDefinitionCache::class => \DI\factory(
        function (ContainerInterface $container): SwaggerDefinitionCache {
            $useCache = $container->get('application.runtime.cache_enabled');
            assert(is_bool($useCache));
            $cacheDir = $container->get('application.runtime.cache_dir');
            assert(is_string($cacheDir));
            return new SwaggerDefinitionCache(
                $useCache ? $cacheDir : null,
                'swagger-definitions.cache',
                'swagger-schema.cache',
                dirname(__DIR__, 3) . '/src'
            );
        }
    ),
    SwaggerSchemaMiddleware::class => \DI\autowire(SwaggerSchemaMiddleware::class)
        ->constructorParameter('developmentModeKey', 'env.development_mode')
];
