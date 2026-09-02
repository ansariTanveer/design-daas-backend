<?php

declare(strict_types=1);

namespace Application\Test\Integration\Main;

use Application\Common\Application\TestHelper\TestCliRequestBuilder;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

final class CliApplicationTest extends TestCase
{
    public function testEnvironmentIsCorrectlyResolved(): void
    {
        $application = TestApplicationFactory::cli();
        $logFile = '/path/to/application.log';

        (new TestCliRequestBuilder())
            ->argv(['version'])
            ->additionalServer(
                [
                    'APPLICATION_LOG_FILE' => $logFile,
                ],
            )
            ->expectExitCode(0)
            ->execute($application);

        self::assertSame(
            $logFile,
            $application->container()->get('config.log.file'),
        );
    }

    public function testVersionRoute(): void
    {
        $application = TestApplicationFactory::cli();

        $response = (new TestCliRequestBuilder())
            ->argv(['version'])
            ->expectExitCode(0)
            ->execute($application);

        self::assertMatchesRegularExpression(
            '=version.+' .
            preg_quote('dev-source', '=') . '=i',
            $response->stdOut(),
        );

        self::assertSame(
            '',
            $response->stdErr(),
        );
    }

    public function testRouteNotFound(): void
    {
        $application = TestApplicationFactory::cli();

        $response = (new TestCliRequestBuilder())
            ->argv(['version-invalid'])
            ->expectExitCode(255)
            ->execute($application);

        self::assertStringContainsString(
            'version-invalid',
            $response->stdErr(),
        );
    }

    public function testFileLogging(): void
    {
        $application = TestApplicationFactory::cli();
        $logFile = self::TEST_TMP_DIR . '/application.log';

        (new TestCliRequestBuilder())
            ->argv(['version'])
            ->additionalServer(
                [
                    'APPLICATION_LOG_FILE' => $logFile,
                    'APPLICATION_LOG_LEVEL' => LogLevel::EMERGENCY,
                ],
            )
            ->prepare($application);

        $logger = $application->container()->get(LoggerInterface::class);
        self::assertInstanceOf(LoggerInterface::class, $logger);

        self::assertFileDoesNotExist($logFile);

        $logger->emergency('Some important log message');
        $application->execute();

        self::assertFileExists($logFile);
        self::assertNotEmpty(file_get_contents($logFile));
    }
}
