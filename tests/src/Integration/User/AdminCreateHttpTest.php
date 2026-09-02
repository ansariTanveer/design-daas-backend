<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\AdminCreationDTO;
use Application\Core\User\DTO\AdminDetailsSwaggerDTO;
use Application\Core\User\Model\Admin;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;

class AdminCreateHttpTest extends TestCase
{
    private EntityManagerInterface $em;
    private HttpApplication $application;
    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        TestApplicationFactory::injectOAuth2Configuration($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->admin = TestEntityBuilder::buildAdmin(['email' => 'first.admin@example.com']);
        $this->em->persist($this->admin);

        $this->em->flush();
        $this->em->clear();
    }

    public function testCreateAdminCommand(): void
    {
        $requestDTO = new AdminCreationDTO();
        $requestDTO->password = '!456MyBigPassword#';
        $requestDTO->name = 'Janice Polito';
        $requestDTO->email = 'janice.polito@example.com';

        $requestJson = json_encode($requestDTO);
        assert(is_string($requestJson));

        $result = (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/admin')
            ->expectResponseCode(200)
            ->body($requestJson)
            ->contentType('application/json')
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->execute($this->application);

        $resultJson = json_decode($result->bodyAsString());
        assert($resultJson instanceof stdClass);

        $responseDTO = AdminDetailsSwaggerDTO::fromJson($resultJson);
        assert($responseDTO instanceof AdminDetailsSwaggerDTO);

        self::assertEquals('Janice Polito', $responseDTO->name);
        self::assertEquals('janice.polito@example.com', $responseDTO->email);
        self::assertEquals('admin', $responseDTO->role);

        // Password should not be repeated in response
        self::assertFalse(isset($responseDTO->password));
    }
}
