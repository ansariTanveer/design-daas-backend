<?php

declare(strict_types=1);

namespace Application\Test\Integration\Desktop;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use stdClass;

class DesktopGroupAddHttpTest extends TestCase
{
    private HttpApplication $application;
    private Admin $admin;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $em = TestApplicationFactory::extractEntityManager($this->application);

        $this->admin = TestEntityBuilder::buildAdmin();
        $em->persist($this->admin);
        $em->flush();
    }

    public function testCreatesDesktopGroup(): void
    {
        $requestDTO = new DesktopGroupDetailsDTO();
        $requestDTO->description = 'Franz jagt im komplett verwahrlosten Taxi quer durch Bayern';

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        $responseContainer = (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/desktop-group')
            ->body($requestJson)
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $responseBody = json_decode((string)$responseContainer->response()->getBody());
        assert($responseBody instanceof stdClass);

        $responseDTO = DesktopGroupDetailsDTO::fromJson($responseBody);

        self::assertTrue(isset($responseDTO->id));
        self::assertSame($requestDTO->description, $responseDTO->description);
    }
}
