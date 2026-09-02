<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\UserCreationDTO;
use Application\Core\User\DTO\UserDetailsSwaggerDTO;
use Application\Test\TestApplicationFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class UserCreationTest extends TestCase
{
    private HttpApplication $application;
    private EntityManagerInterface $em;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->em->flush();
        $this->em->clear();
    }

    public function testCreatesUser(): void
    {
        $requestDTO = new UserCreationDTO();
        $requestDTO->name = "Test User";
        $requestDTO->email = "test@example.com";
        $requestDTO->password = "!SuperSecretPassword";

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        $responseContainer = (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri("/user")
            ->body($requestJson)
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $responseDTO = UserDetailsSwaggerDTO::fromResponse($responseContainer->response());

        self::assertSame($responseDTO->name, $requestDTO->name);
        self::assertCount(0, $responseDTO->groups);
    }

    public function testFailsToCreateUserInvalidEmailAddress(): void
    {
        $requestDTO = new UserCreationDTO();
        $requestDTO->name = "Test User";
        $requestDTO->email = "this is not an email address";
        $requestDTO->password = "!SuperSecretPassword";

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri("/user")
            ->body($requestJson)
            ->contentType("application/json")
            ->expectResponseCode(400)
            ->execute($this->application);
    }
}
