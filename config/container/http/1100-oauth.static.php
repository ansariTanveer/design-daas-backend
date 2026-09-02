<?php

declare(strict_types=1);

use Application\Common\SymfonyRouting\Psr7RouterInterface;
use Application\Core\OAuth2\OAuth2AuthorizationValidator;
use Application\Core\Util\OAuth2\OAuth2AccessControlMiddleware;
use Http\Factory\Guzzle\ResponseFactory;
use Psr\Container\ContainerInterface;

return [
    'config.oauth2.user_test_token_prefix' => null,
    'application.dispatcher.post_route_middleware_queue' => \DI\decorate(
        function (array $previous, ContainerInterface $container): array {
            $router = $container->get('application.dispatcher.router');
            assert($router instanceof Psr7RouterInterface);

            $responseFactory = $container->get(ResponseFactory::class);
            assert($responseFactory instanceof ResponseFactory);

            return array_merge(
                $previous,
                [
                    new OAuth2AccessControlMiddleware(
                        $container,
                        $responseFactory,
                        $router->routeNameAttribute(),
                        ['oauth2-user' => OAuth2AuthorizationValidator::class]
                    ),
                ]
            );
        }
    ),
];
