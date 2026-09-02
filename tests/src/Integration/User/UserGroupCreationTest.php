<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\UserGroupCreationDTO;
use Application\Core\User\DTO\UserGroupDetailsDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Repository\UserGroupRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use stdClass;

class UserGroupCreationTest extends TestCase
{
    private HttpApplication $application;
    private UserGroupRepository $userGroupRepository;

    private Admin $admin;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);
        $em = TestApplicationFactory::extractEntityManager($this->application);

        $this->admin = TestEntityBuilder::buildAdmin();
        $em->persist($this->admin);

        $userGroupRepository = $this->application->container()->get(UserGroupRepository::class);
        assert($userGroupRepository instanceof UserGroupRepository);
        $this->userGroupRepository = $userGroupRepository;

        $em->flush();
        $em->clear();
    }

    public function testCreatesUserGroup(): void
    {
        $requestDTO = new UserGroupCreationDTO();
        $requestDTO->description = "Lorem ipsum dolor sit amet";

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        $responseContainer = (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri("/user_group")
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->body($requestJson)
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $responseBody = json_decode((string)$responseContainer->response()->getBody());
        assert($responseBody instanceof stdClass);

        $responseDTO = UserGroupDetailsDTO::fromJson($responseBody);
        self::assertSame($requestDTO->description, $responseDTO->description);

        $newGroupReloaded = $this->userGroupRepository->find($responseDTO->id);
        self::assertNotNull($newGroupReloaded);
        self::assertSame($requestDTO->description, $newGroupReloaded->description());
    }

    public function testCreatesUserGroupWithEmptyDescription(): void
    {
        $requestDTO = new UserGroupCreationDTO();
        $requestDTO->description = "";

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        (new TestHttpRequestBuilder())
            ->method("POST")
            ->uri("/user_group")
            ->body($requestJson)
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(400)
            ->execute($this->application);
    }
}
