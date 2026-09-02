<?php

declare(strict_types=1);

namespace Application\Test\Integration\Main;

use Application\Common\Application\TestHelper\TestCliRequestBuilder;
use Application\Core\Util\Main\CleanupEvent;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class CleanupCommandTest extends TestCase
{
    public function testMaintenanceCommandSendsEvent(): void
    {
        $eventDispatched = false;
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->expects(self::atLeastOnce())
            ->method('dispatch')
            ->willReturnCallback(
                function ($event) use (&$eventDispatched): void {
                    if ($event instanceof CleanupEvent) {
                        self::assertFalse($eventDispatched);
                        $eventDispatched = true;
                    }
                },
            );

        $application = TestApplicationFactory::cli();
        $application->container()->set(EventDispatcherInterface::class, $dispatcher);

        (new TestCliRequestBuilder())
            ->argv(['maintenance:cleanup'])
            ->expectExitCode(0)
            ->execute($application);

        self::assertTrue($eventDispatched);
    }

    public function testMaintenanceCommandProducesOutputButNoError(): void
    {
        $dispatcher = $this->createMock(EventDispatcherInterface::class);

        $application = TestApplicationFactory::cli();
        $application->container()->set(EventDispatcherInterface::class, $dispatcher);

        $response = (new TestCliRequestBuilder())
            ->argv(['maintenance:cleanup'])
            ->expectExitCode(0)
            ->execute($application);

        self::assertNotEmpty($response->stdOut());
        self::assertEmpty($response->stdErr());
    }
}
