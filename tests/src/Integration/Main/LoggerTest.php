<?php

declare(strict_types=1);

namespace Application\Test\Integration\Main;

use Application\Common\Application\UnexpectedExceptionEvent;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

final class LoggerTest extends TestCase
{
    public function testLoggerCanBeResolved(): void
    {
        $application = TestApplicationFactory::generic();

        $logger = $application->container()->get(LoggerInterface::class);

        self::assertInstanceOf(LoggerInterface::class, $logger);
    }

    public function testUnexpectedExceptionEventTriggersLogMessage(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('critical')
            ->with('Unexpected exception');

        $application = TestApplicationFactory::generic();
        $application->container()->set(LoggerInterface::class, $logger);

        $eventDispatcher = $application->container()->get(EventDispatcherInterface::class);
        self::assertInstanceOf(EventDispatcherInterface::class, $eventDispatcher);

        $eventDispatcher->dispatch(new UnexpectedExceptionEvent(new Exception('Some Message', 42)));
    }
}
