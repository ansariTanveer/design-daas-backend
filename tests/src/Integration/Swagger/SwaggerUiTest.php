<?php

namespace Application\Test\Integration\Swagger;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;

final class SwaggerUiTest extends TestCase
{
    private HttpApplication $application;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
    }

    public function testSwaggerUiCanBeLoaded(): void
    {
        $response = (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/swagger/')
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);

        preg_match('/<title>.+<\/title>/', $response->bodyAsString(), $title);
        self::assertCount(1, $title);
        self::assertIsString($title[0]);
        self::assertStringContainsString('DESIGN', $title[0]);
    }
}
