<?php

namespace Application\Test\Integration;

use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;

class CorsMiddlewareTest extends TestCase
{
    public function testCorsHeadersAreSupplied(): void
    {
        $application = TestApplicationFactory::http();

        $response = (new TestHttpRequestBuilder())
            ->method('OPTIONS')
            ->uri('/version')
            ->expectResponseCode(200)
            ->execute($application);

        self::assertSame(
            '*',
            $response->response()->getHeaderLine('Access-Control-Allow-Origin'),
            'Cannot detect proper CORS headers'
        );
    }
}
