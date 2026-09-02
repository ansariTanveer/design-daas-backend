<?php

declare(strict_types=1);

use Application\Common\Application\ApplicationInterface;

return function (ApplicationInterface $application, ArrayObject $cache): void {
    $key = 'application.event_dispatcher.subscriber_cache';
    $name = __FILE__ . '$' . $key;
    if (!$cache->offsetExists($name)) {
        $cache->offsetSet($name, $application->container()->get($key));
    }
    $application->container()->set($key, $cache->offsetGet($name));
};
