<?php

declare(strict_types=1);

namespace Application\Core\Main;

use Application\Common\Application\ApplicationInterface;
use Application\Common\Application\Cli\CliApplication;
use Application\Common\Application\Cli\SimpleFileCliContext;
use Application\Common\Application\GenericApplication;
use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\Http\SimpleFileHttpContext;
use Application\Common\Application\SimpleFileContext;

final readonly class ApplicationFactory
{
    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    {
    }

    public static function generic(): ApplicationInterface
    {
        return new GenericApplication(
            SimpleFileContext::builder()
                ->scanDir(dirname(__DIR__, 2) . '/config/container')
                ->build(),
        );
    }

    public static function cli(): CliApplication
    {
        return new CliApplication(
            SimpleFileCliContext::builder('application.dispatcher')
                ->scanDir(dirname(__DIR__, 2) . '/config/container')
                ->scanDir(dirname(__DIR__, 2) . '/config/container/cli')
                ->build(),
        );
    }

    public static function http(): HttpApplication
    {
        return new HttpApplication(
            SimpleFileHttpContext::builder('application.dispatcher')
                ->scanDir(dirname(__DIR__, 2) . '/config/container')
                ->scanDir(dirname(__DIR__, 2) . '/config/container/http')
                ->build(),
        );
    }
}
