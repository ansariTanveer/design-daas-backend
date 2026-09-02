<?php

declare(strict_types=1);

namespace Application\Test\Integration\Main;

use Application\Common\Application\Http\InvalidUserInputException;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use stdClass;

final class HttpApplicationTest extends TestCase
{
    public function testEnvironmentIsCorrectlyResolved(): void
    {
        $application = TestApplicationFactory::http();
        $logFile = '/path/to/application.log';

        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/version')
            ->additionalServer(
                [
                    'APPLICATION_LOG_FILE' => $logFile,
                ],
            )
            ->expectResponseCode(200)
            ->execute($application);

        self::assertSame(
            $logFile,
            $application->container()->get('config.log.file'),
        );
    }

    public function testVersionRouteGetRequestReturnsCorrectVersion(): void
    {
        $application = TestApplicationFactory::http();

        $response = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/version')
            ->expectResponseCode(200)
            ->expectContentType('application/json')
            ->execute($application);

        $body = json_decode($response->bodyAsString());

        self::assertInstanceOf(
            stdClass::class,
            $body,
        );

        self::assertTrue(
            property_exists(
                $body,
                'application_version',
            ),
        );

        self::assertSame(
            'dev-source',
            $body->application_version,
        );
    }

    public function testVersionRouteHeadRequestReturnsEmptyBody(): void
    {
        $application = TestApplicationFactory::http();

        $response = (new TestHttpRequestBuilder())
            ->method('HEAD')
            ->uri('/version')
            ->expectResponseCode(200)
            ->expectContentType('application/json')
            ->execute($application);

        $body = $response->bodyAsString();

        self::assertSame(
            '',
            $body,
        );
    }

    public function testRouteNotFound(): void
    {
        $application = TestApplicationFactory::http();

        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/version-invalid')
            ->expectResponseCode(404)
            ->execute($application);
    }

    public function testMethodNotAllowedInvalid(): void
    {
        $application = TestApplicationFactory::http();

        (new TestHttpRequestBuilder())
            ->method('GETInvalid')
            ->uri('/version')
            ->expectResponseCode(405)
            ->execute($application);
    }

    public function testMethodNotAllowedOptions(): void
    {
        $application = TestApplicationFactory::http();

        $response = (new TestHttpRequestBuilder())
            ->method('OPTIONS')
            ->uri('/version')
            ->expectResponseCode(200)
            ->execute($application);

        self::assertSame(
            'GET',
            $response->response()->getHeaderLine('Allow'),
        );
    }

    public function testFileLogging(): void
    {
        $application = TestApplicationFactory::http();
        $logFile = self::TEST_TMP_DIR . '/application.log';

        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/version')
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

    public function testInvalidUserInputExceptionIsConvertedToHttp400Response(): void
    {
        $application = TestApplicationFactory::http();
        $originalResponseFactory = $application->container()->get(ResponseFactoryInterface::class);
        assert($originalResponseFactory instanceof ResponseFactoryInterface);

        $expectedMessage = 'some exception message';
        $mockResponseFactory = $this->createMock(ResponseFactoryInterface::class);
        $mockResponseFactory
            ->expects(self::atLeastOnce())
            ->method('createResponse')
            ->willReturnCallback(
                function (
                    int $code = 200,
                    string $reasonPhrase = '',
                ) use (
                    $originalResponseFactory,
                    $expectedMessage,
                ): ResponseInterface {
                    if ($code === 200) {
                        throw new InvalidUserInputException($expectedMessage);
                    }
                    return $originalResponseFactory->createResponse($code, $reasonPhrase);
                },
            );

        $application->container()->set(ResponseFactoryInterface::class, $mockResponseFactory);

        $response = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/version')
            ->expectResponseCode(400)
            ->expectContentType('application/json')
            ->execute($application);

        self::assertStringContainsString(
            $expectedMessage,
            $response->bodyAsString(),
        );
    }
}
