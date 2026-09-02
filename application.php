<?php

declare(strict_types=1);

use Application\Core\Main\ApplicationFactory;

require __DIR__ . '/config/bootstrap.php';

(function () {
    $application = ApplicationFactory::cli();
    $application->setRuntimeEnvironmentFromGlobals();
    $application->run();
})();
