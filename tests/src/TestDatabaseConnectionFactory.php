<?php

declare(strict_types=1);

namespace Application\Test;

use Application\Common\DoctrineDBAL\DatabaseConnectionFactoryInterface;
use Closure;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\Configuration\Configuration as DoctrineMigrationsConfiguration;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\NullOutput;

final class TestDatabaseConnectionFactory implements DatabaseConnectionFactoryInterface
{
    /**
     * @var self|callable():self|null
     */
    private static $registered;

    public static function registerMySQL(string $dsn): void
    {
        self::$registered = function () use ($dsn): self {
            $genericApplication = TestApplicationFactory::generic();
            $genericApplication->container()->set('config.database.dsn', $dsn);

            $connectionFactory = $genericApplication->container()->get('application.database.connection_factory');
            assert($connectionFactory instanceof DatabaseConnectionFactoryInterface);
            $connection = $connectionFactory->build();
            assert($connection->getDatabasePlatform()->getName() === 'mysql');

            $connection->executeQuery(/** @lang MySQL */ "SET FOREIGN_KEY_CHECKS = 0");
            $selectTablesSql = /** @lang MySQL */
                "SHOW TABLES LIKE '%'";
            $rows = $connection->executeQuery($selectTablesSql)->fetchAllNumeric();
            foreach ($rows as $row) {
                $connection->executeQuery(/** @lang MySQL */ "DROP TABLE `" . $row[0] . "`");
            }
            $connection->executeQuery(/** @lang MySQL */ "SET FOREIGN_KEY_CHECKS = 1");

            $migrationsConfiguration =
                $genericApplication->container()->get('application.database.migrations_configuration');
            assert($migrationsConfiguration instanceof DoctrineMigrationsConfiguration);
            $migrateCommand = new MigrateCommand();
            $migrateCommand->setConnection($connection);
            $migrateCommand->setMigrationConfiguration($migrationsConfiguration);
            $commandInput = new ArgvInput([], $migrateCommand->getDefinition());
            $commandInput->setInteractive(false);
            $migrateCommand->execute($commandInput, new NullOutput());

            $migrationsTable = $genericApplication->container()->get('application.database.migrations_table');
            $cleaner = function (Connection $connection) use ($migrationsTable): void {
                $connection->executeQuery(/** @lang MySQL */ "SET FOREIGN_KEY_CHECKS = 0");
                $selectTablesSql = /** @lang MySQL */
                    "SHOW TABLES LIKE '%'";
                $rows = $connection->executeQuery($selectTablesSql)->fetchAllNumeric();
                foreach ($rows as $row) {
                    if ($row[0] === $migrationsTable) {
                        continue;
                    }
                    $connection->executeQuery(/** @lang MySQL */ "TRUNCATE TABLE `" . $row[0] . "`");
                }
                $connection->executeQuery(/** @lang MySQL */ "SET FOREIGN_KEY_CHECKS = 1");
            };
            return new self($connectionFactory, $cleaner);
        };
    }

    public static function getRegistered(): self
    {
        if (self::$registered === null) {
            TestCase::markTestSkipped('No test database configured');
        }
        if (!(self::$registered instanceof self)) {
            self::$registered = (self::$registered)();
        }
        return self::$registered;
    }

    public static function reset(): void
    {
        if (self::$registered instanceof self) {
            self::$registered->executeCleaner = true;
            foreach (self::$registered->connections as $oldConnection) {
                $oldConnection->close();
            }
            self::$registered->connections = [];
        }
    }

    private DatabaseConnectionFactoryInterface $parentFactory;

    private Closure $cleaner;

    private bool $executeCleaner = true;

    /**
     * @var Connection[]
     */
    private array $connections = [];

    private function __construct(DatabaseConnectionFactoryInterface $parentFactory, Closure $cleaner)
    {
        $this->parentFactory = $parentFactory;
        $this->cleaner = $cleaner;
    }

    public function build(): Connection
    {
        $connection = $this->parentFactory->build();
        if ($this->executeCleaner) {
            ($this->cleaner)($connection);
            $this->executeCleaner = false;
        }
        $this->connections[] = $connection;
        return $connection;
    }
}
