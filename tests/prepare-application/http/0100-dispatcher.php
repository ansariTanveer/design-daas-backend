<?php

declare(strict_types=1);

use Application\Common\Application\Http\HttpApplicationInterface;

return function (HttpApplicationInterface $application, ArrayObject $cache): void {
    $key = 'application.dispatcher.route_cache';
    $name = __FILE__ . '$' . $key;
    if (!$cache->offsetExists($name)) {
        $cache->offsetSet($name, $application->container()->get($key));
    }
    $application->container()->set($key, $cache->offsetGet($name));
};
