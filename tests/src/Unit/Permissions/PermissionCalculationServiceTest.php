<?php

declare(strict_types=1);

namespace Application\Test\Unit\Permissions;

use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Repository\EndpointGroupRepository;
use Application\Core\Permissions\Service\PermissionCalculationService;
use Application\Core\Permissions\Service\PermissionsService;
use Application\Test\Fixture\EndpointFixture;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use DI\DependencyException;
use DI\NotFoundException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @psalm-import-type EndpointFixtureOutput from EndpointFixture
 */
final class PermissionCalculationServiceTest extends TestCase
{
    private PermissionCalculationService $sut;
    private EndpointFixture $endpointFixture;
    private EntityManagerInterface $em;

    /**
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($application);

        $this->em = TestApplicationFactory::extractEntityManager($application);

        /** @var PermissionCalculationService $sut */
        $sut = $application->container()->get(PermissionCalculationService::class);
        $this->sut = $sut;

        /** @var EndpointFixture $endpointFixture */
        $endpointFixture = $application->container()->get(EndpointFixture::class);
        $this->endpointFixture = $endpointFixture;
    }


    /**
     * @return iterable<
     *     string,
     *      array{
     *          endpointUserAccess: ?AccessEnum,
     *          endpointUserGroupAccess: ?AccessEnum,
     *          endpointGroupUserAccess: ?AccessEnum,
     *          endpointGroupUserGroupAccess: ?AccessEnum,
     *          expectedResult: AccessEnum
     *      }
     * >
     */
    public static function data(): iterable
    {
        yield 'default' => [
            'endpointUserAccess' => null,
            'endpointUserGroupAccess' => null,
            'endpointGroupUserAccess' => null,
            'endpointGroupUserGroupAccess' => null,
            'expectedResult' => AccessEnum::DENY,
        ];

        yield 'withEndpointUserAccess' => [
            'endpointUserAccess' => AccessEnum::ALLOW,
            'endpointUserGroupAccess' => AccessEnum::DENY,
            'endpointGroupUserAccess' => AccessEnum::DENY,
            'endpointGroupUserGroupAccess' => AccessEnum::DENY,
            'expectedResult' => AccessEnum::ALLOW,
        ];

        yield 'withEndpointUserGroupAccess' => [
            'endpointUserAccess' => null,
            'endpointUserGroupAccess' => AccessEnum::ALLOW,
            'endpointGroupUserAccess' => AccessEnum::DENY,
            'endpointGroupUserGroupAccess' => AccessEnum::DENY,
            'expectedResult' => AccessEnum::ALLOW,
        ];

        yield 'withEndpointGroupUserAccess' => [
            'endpointUserAccess' => null,
            'endpointUserGroupAccess' => null,
            'endpointGroupUserAccess' => AccessEnum::ALLOW,
            'endpointGroupUserGroupAccess' => AccessEnum::DENY,
            'expectedResult' => AccessEnum::ALLOW,
        ];

        yield 'withEndpointGroupUserGroupAccess' => [
            'endpointUserAccess' => null,
            'endpointUserGroupAccess' => null,
            'endpointGroupUserAccess' => null,
            'endpointGroupUserGroupAccess' => AccessEnum::ALLOW,
            'expectedResult' => AccessEnum::ALLOW,
        ];
    }

    /**
     * @dataProvider data
     */
    public function testPermissionCalculation(
        ?AccessEnum $endpointUserAccess,
        ?AccessEnum $endpointUserGroupAccess,
        ?AccessEnum $endpointGroupUserAccess,
        ?AccessEnum $endpointGroupUserGroupAccess,
        AccessEnum $expectedResult
    ): void {
        $data = $this->endpointFixture->load(
            $endpointUserAccess,
            $endpointUserGroupAccess,
            $endpointGroupUserAccess,
            $endpointGroupUserGroupAccess
        );

        $this->em->clear();

        self::assertEquals(
            $expectedResult,
            $this->sut->getPermission(
                $data->endpoint->functionName(),
                $data->user->id()
            )
        );
    }
}
