<?php

declare(strict_types=1);

namespace Application\Test\Integration\Desktop;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Desktop\DTO\DesktopDetailsDTO;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;

class DesktopAddHttpTest extends TestCase
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

    public function testCreatesDesktop(): void
    {
        $requestDTO = new DesktopDetailsDTO();
        $requestDTO->description = "Lorem ipsum dolor sit amet";

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        $responseContainer = (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri("/desktop")
            ->body($requestJson)
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $responseDTO = DesktopDetailsDTO::fromResponse($responseContainer->response());

        self::assertTrue(isset($responseDTO->id));
        self::assertGreaterThan(0, $responseDTO->id);
        self::assertSame($requestDTO->description, $responseDTO->description);
    }
}
