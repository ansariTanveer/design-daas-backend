<?php

declare(strict_types=1);

namespace Application\Test\Integration\Main;

use Application\Common\DoctrineDBAL\DatabaseConnectionFactoryInterface;
use Application\Core\Main\ExceptionHandlerEventSubscriber;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;

final class DatabaseConnectionTest extends TestCase
{
    public function testMigrationsRun(): void
    {
        $application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($application);

        $dbFactory = $application->container()->get('application.database.connection_factory');
        assert($dbFactory instanceof DatabaseConnectionFactoryInterface);

        self::assertNotEmpty($dbFactory->build()->getSchemaManager()->listTables());
    }

    public function testEntityManagerIsLoadedCorrectly(): void
    {
        $application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($application);

        $em = TestApplicationFactory::extractEntityManager($application);

        self::assertTrue($em->isOpen());
    }

    public function testEntityListenerResolverIsUsingDependencyInjection(): void
    {
        $application = TestApplicationFactory::generic();
        TestApplicationFactory::injectDatabaseConnection($application);

        $em = TestApplicationFactory::extractEntityManager($application);

        $resolvedEntry = $em->getConfiguration()->getEntityListenerResolver()->resolve(
            ExceptionHandlerEventSubscriber::class,
        );

        self::assertInstanceOf(
            ExceptionHandlerEventSubscriber::class,
            $resolvedEntry,
        );
    }
}
