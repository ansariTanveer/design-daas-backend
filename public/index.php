<?php

declare(strict_types=1);

use Application\Core\Main\ApplicationFactory;

require dirname(__DIR__) . '/config/bootstrap.php';

(function () {
    $application = ApplicationFactory::http();
    $application->setRuntimeEnvironmentFromGlobals();
    $application->run();
})();
