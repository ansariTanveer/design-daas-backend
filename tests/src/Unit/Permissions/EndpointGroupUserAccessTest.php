<?php

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Common\Application\ApplicationInterface;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Model\EndpointGroupUserAccess;
use Application\Core\Permissions\Repository\EndpointGroupUserAccessRepository;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

final class EndpointGroupUserAccessTest extends TestCase
{
    private ApplicationInterface $application;
    private EntityManagerInterface $em;
    private EndpointGroupUserAccessRepository $sut;

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

        /** @var EndpointGroupUserAccessRepository $sut */
        $sut = $this->application->container()->get(EndpointGroupUserAccessRepository::class);
        $this->sut = $sut;
    }

    public function testStoresAndRestoresEndpoint(): void
    {
        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $endpointGroupUserAccess = TestEntityBuilder::buildEndpointGroupUserAccess(
            $endpointGroup,
            $user
        );
        $this->em->persist($endpointGroupUserAccess);

        $this->em->flush();
        $this->em->clear();

        $endpointGroupUserAccessRestored = $this->sut->find($endpointGroupUserAccess->id());

        self::assertInstanceOf(EndpointGroupUserAccess::class, $endpointGroupUserAccessRestored);
        self::assertEquals(
            $endpointGroupUserAccess->id(),
            $endpointGroupUserAccessRestored->id()
        );
        self::assertEquals(
            $endpointGroupUserAccess->endpointGroup()->uniqueGroupName(),
            $endpointGroupUserAccessRestored->endpointGroup()->uniqueGroupName()
        );
        self::assertEquals(
            $endpointGroupUserAccess->user()->id(),
            $endpointGroupUserAccessRestored->user()->id()
        );
        self::assertEquals(
            $endpointGroupUserAccess->relation(),
            $endpointGroupUserAccessRestored->relation()
        );
    }

    public function testOnlyOneRelationPerEndpointAndUser(): void
    {
        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $this->application->container()->get(EndpointFixture::class);
        $data = $endpointFixture->load();

        self::expectException(UniqueConstraintViolationException::class);

        $endpointUserAccess2 = TestEntityBuilder::buildEndpointGroupUserAccess(
            $data->endpointGroup,
            $data->user,
            ['relation' => AccessEnum::DENY]
        );
        $this->em->persist($endpointUserAccess2);

        $this->em->flush();
        $this->em->clear();
    }
}
