<?php

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Model\EndpointGroup;
use Application\Core\Permissions\Repository\EndpointGroupRepository;
use Application\Core\Permissions\Repository\EndpointGroupUserAccessRepository;
use Application\Core\Permissions\Repository\EndpointGroupUserGroupAccessRepository;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

final class EndpointGroupTest extends TestCase
{
    private ApplicationInterface $application;

    private EntityManagerInterface $em;

    private EndpointGroupRepository $sut;

    private EndpointGroupUserAccessRepository $endpointGroupUserAccessRepository;

    private EndpointGroupUserGroupAccessRepository $endpointGroupUserGroupAccessRepository;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        /** @var EndpointGroupRepository $endpointGroupRepository */
        $endpointGroupRepository = $this->application->container()->get(EndpointGroupRepository::class);
        $this->sut = $endpointGroupRepository;

        /** @var EndpointGroupUserAccessRepository $endpointGroupUserAccessRepository */
        $endpointGroupUserAccessRepository =
            $this->application->container()->get(EndpointGroupUserAccessRepository::class);
        $this->endpointGroupUserAccessRepository = $endpointGroupUserAccessRepository;

        /** @var EndpointGroupUserGroupAccessRepository $endpointGroupUserGroupAccessRepository */
        $endpointGroupUserGroupAccessRepository =
            $this->application->container()->get(EndpointGroupUserGroupAccessRepository::class);
        $this->endpointGroupUserGroupAccessRepository = $endpointGroupUserGroupAccessRepository;
    }

    public function testStoresAndRestoresEndpoint(): void
    {
        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $this->em->flush();
        $this->em->clear();

        $endpointGroupRestored = $this->sut->find($endpointGroup->uniqueGroupName());

        self::assertInstanceOf(EndpointGroup::class, $endpointGroupRestored);
        self::assertEquals($endpointGroup->uniqueGroupName(), $endpointGroupRestored->uniqueGroupName());

        self::assertCount(1, $endpointGroupRestored->endpoints());
        self::assertEquals($endpoint->id(), $endpointGroupRestored->endpoints()[0]->id());
    }

    public function testRemoveCascades(): void
    {
        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $data = $endpointFixture->load();

        self::assertCount(1, $this->endpointGroupUserAccessRepository->findAll());
        self::assertCount(1, $this->endpointGroupUserGroupAccessRepository->findAll());

        $this->em->remove($data->endpointGroup);

        $this->em->flush();
        $this->em->clear();

        self::assertCount(0, $this->endpointGroupUserAccessRepository->findAll());
        self::assertCount(0, $this->endpointGroupUserGroupAccessRepository->findAll());
    }

    public function testAddsUserGroupAccess(): void
    {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($user);
        $this->em->persist($userGroup);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $endpointGroupUserGroupAccess = TestEntityBuilder::buildEndpointGroupUserGroupAccess(
            $endpointGroup,
            $userGroup,
            ['relation' => AccessEnum::ALLOW]
        );
        $this->em->persist($endpointGroupUserGroupAccess);

        $endpointGroup->addUserGroupAccess($endpointGroupUserGroupAccess);

        $this->em->flush();

        self::assertCount(1, $this->endpointGroupUserGroupAccessRepository->findAll());
    }
}
