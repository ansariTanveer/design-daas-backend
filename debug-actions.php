<?php

declare(strict_types=1);

use Application\Common\SymfonyConsole\CommandCacheInterface;
use Application\Common\SymfonyRouting\RouteCacheInterface;
use Application\Core\Main\ApplicationFactory;

require __DIR__ . '/config/bootstrap.php';

(function () use ($argv) {
    /*
     * First argument is interpreted as filter
     */
    $filter = $argv[1] ?? null;
    if ($filter !== null && strlen($filter) < 1) {
        $filter = null;
    }
    echo sprintf('Filter: %1$s' . PHP_EOL, $filter);

    /*
     * CLI commands
     */
    echo sprintf(PHP_EOL . '%1$s CLI commands %1$s' . PHP_EOL, '==========');
    $cliApplication = ApplicationFactory::cli();
    $commands = $cliApplication->container()->get('application.dispatcher.command_cache');
    assert($commands instanceof CommandCacheInterface);
    foreach ($commands->loadCommandList($cliApplication->container()) as $command) {
        $name = $command->getName();
        if ($filter !== null && stripos($name, $filter) === false) {
            continue;
        }
        $data = $command->getAnnotationData();
        echo sprintf(
            PHP_EOL . 'Command: %1$s' . PHP_EOL . 'Method: %2$s::%3$s' . PHP_EOL,
            $name,
            $data['_commandClass'] ?? '?',
            $data['_commandMethod'] ?? '?',
        );
    }

    /*
     * HTTP routes
     */
    echo sprintf(PHP_EOL . '%1$s HTTP routes %1$s' . PHP_EOL, '==========');
    $httpApplication = ApplicationFactory::http();
    $routes = $httpApplication->container()->get('application.dispatcher.route_cache');
    assert($routes instanceof RouteCacheInterface);
    foreach ($routes->loadRouteCollection()->all() as $route) {
        $name = $route->getPath();
        if ($filter !== null && stripos($name, $filter) === false) {
            continue;
        }
        echo sprintf(
            PHP_EOL . 'Route: %1$s %2$s' . PHP_EOL . 'Method: %3$s::%4$s' . PHP_EOL,
            implode('|', $route->getMethods()),
            $name,
            $route->getDefault('_controllerClass') ?? '?',
            $route->getDefault('_controllerMethod') ?? '?',
        );
    }
})();
