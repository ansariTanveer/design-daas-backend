<?php

declare(strict_types=1);

use Application\Common\Application\Http\HttpDispatcherInterface;
use Application\Common\Application\Http\InvalidUserInputHandlerMiddleware;
use Application\Common\Application\Http\SymfonyRoutingHttpDispatcher;
use Application\Common\HttpBaseUrl\BaseUrlResolverInterface;
use Application\Common\HttpBaseUrl\RequestAttributeBaseUrlResolverMiddleware;
use Application\Common\HttpMiddleware\RemoveBodyFromHeadResponsesMiddleware;
use Application\Common\SymfonyRouting\Psr7Router;
use Application\Common\SymfonyRouting\Psr7RouterInterface;
use Application\Common\SymfonyRouting\RouteCache;
use Application\Common\SymfonyRouting\RouteCacheInterface;
use Application\Common\SymfonyRouting\RouteCollector;
use Application\Common\SymfonyRouting\RouteCollectorInterface;
use BjoernGoetschke\Psr7BaseUrl\BaseUrlRequestTargetMiddleware;
use DI\Container;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;

return [
    'application.dispatcher.base_url_attribute' => \DI\value('request.base_url'),
    'application.dispatcher.base_url_middleware' => \DI\autowire(BaseUrlRequestTargetMiddleware::class)
        ->constructorParameter(0, \DI\get('application.dispatcher.base_url_attribute')),
    'application.dispatcher.base_url_resolver' => \DI\autowire(RequestAttributeBaseUrlResolverMiddleware::class)
        ->constructorParameter(0, \DI\get('application.dispatcher.base_url_attribute')),
    'application.dispatcher.proxy_base_url_attribute' => \DI\value('request.proxy_base_url'),
    'application.dispatcher.proxy_base_url_middleware' => \DI\factory(
        function (ContainerInterface $container): Closure {
            $trustProxyHeaders = $container->get('config.dispatcher.trust_proxy_headers');
            $trustProxyHeaders = is_string($trustProxyHeaders) && strlen($trustProxyHeaders) > 0;
            $originalAttribute = $container->get('application.dispatcher.base_url_attribute');
            assert(is_string($originalAttribute));
            $proxyAttribute = $container->get('application.dispatcher.proxy_base_url_attribute');
            assert(is_string($proxyAttribute));
            return function (
                ServerRequestInterface $request,
                callable $next,
            ) use (
                $trustProxyHeaders,
                $originalAttribute,
                $proxyAttribute,
            ): ResponseInterface {
                $baseUrl = $request->getAttribute($originalAttribute);
                assert($baseUrl instanceof UriInterface);
                $isForwarded = $request->hasHeader('X-Forwarded-Proto') &&
                    $request->hasHeader('X-Forwarded-Host') &&
                    $request->hasHeader('X-Forwarded-Port') &&
                    $request->hasHeader('X-Forwarded-Uri');
                if ($trustProxyHeaders && $isForwarded) {
                    $scheme = $request->getHeaderLine('X-Forwarded-Proto') === 'https' ? 'https' : 'http';
                    $host = $request->getHeaderLine('X-Forwarded-Host');
                    $port = (int)$request->getHeaderLine('X-Forwarded-Port');
                    $path = $request->getHeaderLine('X-Forwarded-Uri');
                    if (str_ends_with($path, $request->getRequestTarget())) {
                        $path = substr($path, 0, -strlen($request->getRequestTarget()));
                    }
                    $baseUrl = $baseUrl->withScheme($scheme)
                        ->withHost($host)
                        ->withPort($port)
                        ->withPath($path);
                }
                return $next($request->withAttribute($proxyAttribute, $baseUrl));
            };
        },
    ),
    'application.dispatcher.proxy_base_url_resolver' => \DI\autowire(RequestAttributeBaseUrlResolverMiddleware::class)
        ->constructorParameter(0, \DI\get('application.dispatcher.proxy_base_url_attribute')),
    'application.dispatcher.route_collector' => \DI\factory(
        function (ContainerInterface $container): RouteCollectorInterface {
            $collector = new RouteCollector();
            $collector->addSearchPath(
                dirname(__DIR__, 3) . '/src',
                '*HttpController.php',
            );
            return $collector;
        },
    ),
    'application.dispatcher.route_cache' => \DI\factory(
        function (ContainerInterface $container): RouteCacheInterface {
            $collector = $container->get('application.dispatcher.route_collector');
            assert($collector instanceof RouteCollector);
            $useCache = $container->get('application.runtime.cache_enabled');
            assert(is_bool($useCache));
            $cacheDir = $container->get('application.runtime.cache_dir');
            assert($cacheDir === null || is_string($cacheDir));
            return new RouteCache(
                $collector,
                $useCache ?
                    sprintf('%1$s/http-routes.cache', $cacheDir) :
                    null,
            );
        },
    ),
    'application.dispatcher.pre_route_middleware_queue' => \DI\factory(
        function (ContainerInterface $container): array {
            return [
                $container->get('application.dispatcher.base_url_middleware'),
                $container->get('application.dispatcher.base_url_resolver'),
                $container->get('application.dispatcher.proxy_base_url_middleware'),
                $container->get('application.dispatcher.proxy_base_url_resolver'),
                $container->get(RemoveBodyFromHeadResponsesMiddleware::class),
            ];
        },
    ),
    'application.dispatcher.post_route_middleware_queue' => \DI\factory(
        function (ContainerInterface $container): array {
            return [
                $container->get(InvalidUserInputHandlerMiddleware::class),
            ];
        },
    ),
    'application.dispatcher.router' => \DI\autowire(Psr7Router::class)
        ->constructorParameter(0, \DI\get('application.dispatcher.base_url_resolver'))
        ->constructorParameter(1, \DI\get('application.dispatcher.route_cache'))
        ->constructorParameter(3, \DI\get(EventDispatcherInterface::class)),
    'application.dispatcher' => \DI\factory(
        function (ContainerInterface $container): HttpDispatcherInterface {
            /** @var array<callable> $preRouteMiddlewareQueue */
            $preRouteMiddlewareQueue = $container->get('application.dispatcher.pre_route_middleware_queue');
            /** @var array<callable> $postRouteMiddlewareQueue */
            $postRouteMiddlewareQueue = $container->get('application.dispatcher.post_route_middleware_queue');
            $dispatcherContainer = $container->get(Container::class);
            assert($dispatcherContainer instanceof Container);
            $router = $container->get('application.dispatcher.router');
            assert($router instanceof Psr7RouterInterface);
            $eventDispatcher = $container->get(EventDispatcherInterface::class);
            assert($eventDispatcher instanceof EventDispatcherInterface);
            return new SymfonyRoutingHttpDispatcher(
                $dispatcherContainer,
                $router,
                $preRouteMiddlewareQueue,
                $postRouteMiddlewareQueue,
                $eventDispatcher,
            );
        },
    ),
    BaseUrlResolverInterface::class => \DI\get('application.dispatcher.proxy_base_url_resolver'),
];
