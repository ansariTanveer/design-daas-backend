<?php

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Model\EndpointUserGroupAccess;
use Application\Core\Permissions\Repository\EndpointUserGroupAccessRepository;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class EndpointUserGroupAccessTest extends TestCase
{
    private ApplicationInterface $application;
    private EntityManagerInterface $em;
    private EndpointUserGroupAccessRepository $sut;

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

        /** @var EndpointUserGroupAccessRepository $sut */
        $sut = $this->application->container()->get(EndpointUserGroupAccessRepository::class);
        $this->sut = $sut;
    }

    public function testStoresAndRestoresEndpoint(): void
    {
        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $this->em->persist($userGroup);

        $endpointUserGroupAccess = TestEntityBuilder::buildEndpointUserGroupAccess(
            $endpoint,
            $userGroup
        );
        $this->em->persist($endpointUserGroupAccess);

        $this->em->flush();
        $this->em->clear();

        $endpointUserAccessRestored = $this->sut->find($endpointUserGroupAccess->id());

        self::assertInstanceOf(EndpointUserGroupAccess::class, $endpointUserAccessRestored);
        self::assertEquals($endpointUserGroupAccess->id(), $endpointUserAccessRestored->id());
        self::assertEquals($endpointUserGroupAccess->endpoint()->id(), $endpointUserAccessRestored->endpoint()->id());
        self::assertEquals($endpointUserGroupAccess->userGroup()->id(), $endpointUserAccessRestored->userGroup()->id());
        self::assertEquals($endpointUserGroupAccess->relation(), $endpointUserGroupAccess->relation());
    }

    public function testOnlyOneRelationPerEndpointAndUser(): void
    {
        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $data = $endpointFixture->load();

        self::expectException(UniqueConstraintViolationException::class);

        $endpointUserGroupAccess2 = TestEntityBuilder::buildEndpointUserGroupAccess(
            $data->endpoint,
            $data->userGroup,
            ['relation' => AccessEnum::DENY]
        );
        $this->em->persist($endpointUserGroupAccess2);

        $this->em->flush();
        $this->em->clear();
    }
}
