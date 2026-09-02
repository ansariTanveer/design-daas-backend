<?php

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Model\EndpointGroupUserGroupAccess;
use Application\Core\Permissions\Repository\EndpointGroupUserGroupAccessRepository;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class EndpointGroupUserGroupAccessTest extends TestCase
{
    private ApplicationInterface $application;
    private EntityManagerInterface $em;
    private EndpointGroupUserGroupAccessRepository $sut;

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

        /** @var EndpointGroupUserGroupAccessRepository $sut */
        $sut = $this->application->container()->get(EndpointGroupUserGroupAccessRepository::class);
        $this->sut = $sut;
    }

    public function testStoresAndRestoresEndpoint(): void
    {
        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $endpointGroupUserGroupAccess = TestEntityBuilder::buildEndpointGroupUserGroupAccess(
            $endpointGroup,
            $userGroup
        );
        $this->em->persist($endpointGroupUserGroupAccess);

        $this->em->flush();
        $this->em->clear();

        $endpointGroupUserGroupAccessRestored = $this->sut->find($endpointGroupUserGroupAccess->id());

        self::assertInstanceOf(EndpointGroupUserGroupAccess::class, $endpointGroupUserGroupAccessRestored);
        self::assertEquals(
            $endpointGroupUserGroupAccess->id(),
            $endpointGroupUserGroupAccessRestored->id()
        );
        self::assertEquals(
            $endpointGroupUserGroupAccess->endpointGroup()->uniqueGroupName(),
            $endpointGroupUserGroupAccessRestored->endpointGroup()->uniqueGroupName()
        );
        self::assertEquals(
            $endpointGroupUserGroupAccess->userGroup()->id(),
            $endpointGroupUserGroupAccessRestored->userGroup()->id()
        );
        self::assertEquals(
            $endpointGroupUserGroupAccess->relation(),
            $endpointGroupUserGroupAccessRestored->relation()
        );
    }

    public function testOnlyOneRelationPerEndpointAndUser(): void
    {
        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $data = $endpointFixture->load();

        self::expectException(UniqueConstraintViolationException::class);

        $endpointUserAccess2 = TestEntityBuilder::buildEndpointGroupUserGroupAccess(
            $data->endpointGroup,
            $data->userGroup,
            ['relation' => AccessEnum::DENY]
        );
        $this->em->persist($endpointUserAccess2);

        $this->em->flush();
        $this->em->clear();
    }
}
