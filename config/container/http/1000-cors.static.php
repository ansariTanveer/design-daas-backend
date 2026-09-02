<?php

declare(strict_types=1);

use Application\Common\HttpMiddleware\NaiveCORSResponseHeadersMiddleware;
use Psr\Container\ContainerInterface;

return [
    'application.dispatcher.pre_route_middleware_queue' => \DI\decorate(
        function (array $previous, ContainerInterface $container): array {
            return array_merge(
                $previous,
                [
                    $container->get(NaiveCORSResponseHeadersMiddleware::class),
                ]
            );
        }
    ),
];
