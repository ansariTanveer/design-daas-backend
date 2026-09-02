<?php

declare(strict_types=1);

use Application\Common\Application\Cli\CliApplicationInterface;

return function (CliApplicationInterface $application, ArrayObject $cache): void {
    $key = 'application.dispatcher.command_cache';
    $name = __FILE__ . '$' . $key;
    if (!$cache->offsetExists($name)) {
        $cache->offsetSet($name, $application->container()->get($key));
    }
    $application->container()->set($key, $cache->offsetGet($name));
};
