<?php

declare(strict_types=1);

namespace Application\Test\Integration\User;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\User\DTO\UserGroupCreationDTO;
use Application\Core\User\DTO\UserGroupDetailsDTO;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\UserGroup;
use Application\Core\User\Repository\UserGroupRepository;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;

class UserGroupUpdateTest extends TestCase
{
    private HttpApplication $application;
    private UserGroupRepository $userGroupRepository;

    private UserGroup $initialGroup;

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

        $userGroupRepository = $this->application->container()->get(UserGroupRepository::class);
        assert($userGroupRepository instanceof UserGroupRepository);
        $this->userGroupRepository = $userGroupRepository;

        $this->admin = TestEntityBuilder::buildAdmin();
        $em->persist($this->admin);

        $this->initialGroup = TestEntityBuilder::buildUserGroup(["description" => "Lorem ipsum dolor sit amet"]);
        $em->persist($this->initialGroup);
        $em->flush();
    }

    public function testUpdatesGroup(): void
    {
        $requestDTO = new UserGroupCreationDTO();
        $requestDTO->description = "consectetur adipiscing elit";

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        $responseContainer = (new TestHttpRequestBuilder())
            ->method("PATCH")
            ->uri(sprintf('/user_group/%1$d', $this->initialGroup->id()))
            ->body($requestJson)
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(200)
            ->execute($this->application);

        $responseDTO = UserGroupDetailsDTO::fromResponse($responseContainer->response());
        self::assertSame($requestDTO->description, $responseDTO->description);

        $newGroupReloaded = $this->userGroupRepository->find($responseDTO->id);
        self::assertInstanceOf(UserGroup::class, $newGroupReloaded);
        self::assertSame($requestDTO->description, $newGroupReloaded->description());
    }

    public function testUpdatesUserGroupWithEmptyDescription(): void
    {
        $requestDTO = new UserGroupCreationDTO();
        $requestDTO->description = "";

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        (new TestHttpRequestBuilder())
            ->method("PATCH")
            ->uri(sprintf('/user_group/%1$d', $this->initialGroup->id()))
            ->body($requestJson)
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->contentType("application/json")
            ->expectResponseCode(400)
            ->execute($this->application);
    }

    public function testUpdatesUserGroupThatDoesNotExist(): void
    {
        $requestDTO = new UserGroupCreationDTO();
        $requestDTO->description = "consectetur adipiscing elit";

        $requestJson = json_encode($requestDTO);
        self::assertIsString($requestJson);

        (new TestHttpRequestBuilder())
            ->method("PATCH")
            ->uri(sprintf('/user_group/%1$d', 5000))
            ->additionalServer(TestCase::authorizeAdmin($this->admin))
            ->body($requestJson)
            ->contentType("application/json")
            ->expectResponseCode(400)
            ->execute($this->application);
    }
}
