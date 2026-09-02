<?php

declare(strict_types=1);

use Application\Common\Application\ApplicationInterface;

return function (ApplicationInterface $application, ArrayObject $cache): void {
    $application->container()->set(
        'config.mailer.from_email',
        'dummy.sender@example.com'
    );
    $application->container()->set(
        'config.mailer.from_name',
        'Dummy sender'
    );
};
