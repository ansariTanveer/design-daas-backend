<?php

declare(strict_types=1);

namespace Application\Test\Unit\Main;

use Application\Core\Main\ApplicationFactory;
use Application\Test\TestCase;

final class ApplicationFactoryTest extends TestCase
{
    public function testGenericApplicationCanBeBuild(): void
    {
        self::assertNotEmpty(ApplicationFactory::generic()->container()->get('application.runtime.version_details'));
    }

    public function testCliApplicationCanBeBuild(): void
    {
        self::assertNotEmpty(ApplicationFactory::cli()->container()->get('application.runtime.version_details'));
    }

    public function testHttpApplicationCanBeBuild(): void
    {
        self::assertNotEmpty(ApplicationFactory::http()->container()->get('application.runtime.version_details'));
    }
}
