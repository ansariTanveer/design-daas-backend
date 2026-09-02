<?php

declare(strict_types=1);

use Application\Common\Application\ApplicationInterface;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;

return function (ApplicationInterface $application, ArrayObject $cache): void {
    $application->container()->set(
        HandlerStack::class,
        new HandlerStack(new MockHandler()),
    );
};
