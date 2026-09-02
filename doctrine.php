<?php

// https://www.doctrine-project.org/projects/doctrine-orm/en/2.13/reference/tools.html
// https://www.doctrine-project.org/projects/doctrine-migrations/en/2.3/reference/custom-integration.html

declare(strict_types=1);

use Application\Core\Main\ApplicationFactory;
use Application\Test\TestApplicationFactory;
use Application\Test\TestDatabaseConnectionFactory;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Doctrine\Migrations\Configuration\Configuration as MigrationsConfiguration;
use Doctrine\Migrations\Tools\Console\Command as MigrationsCommand;
use Doctrine\Migrations\Tools\Console\Helper\ConfigurationHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner;
use Doctrine\ORM\Tools\Console\Helper\EntityManagerHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Helper\QuestionHelper;

require __DIR__ . '/config/bootstrap.php';

(function () {
    $useTestDatabase = false;
    if (($_SERVER['argv'][1] ?? null) === '--use-test-database') {
        unset($_SERVER['argv'][1]);
        $_SERVER['argc']--;
        $useTestDatabase = true;
    }

    $application = ApplicationFactory::cli();
    $application->setRuntimeEnvironmentFromGlobals();

    if ($useTestDatabase) {
        TestDatabaseConnectionFactory::registerMySQL($_SERVER['TEST_DATABASE_DSN']);
        TestApplicationFactory::injectDatabaseConnection($application);
    }

    $entityManager = $application->container()->get(EntityManagerInterface::class);
    assert($entityManager instanceof EntityManagerInterface);
    $migrationsConfiguration = $application->container()->get('application.database.migrations_configuration');
    assert($migrationsConfiguration instanceof MigrationsConfiguration);
    $connection = $entityManager->getConnection();

    $helperSet = new HelperSet();
    $helperSet->set(new QuestionHelper(), 'question');
    $helperSet->set(new EntityManagerHelper($entityManager), 'entityManager');
    $helperSet->set(new ConnectionHelper($connection), 'db');
    $helperSet->set(new ConfigurationHelper($connection, $migrationsConfiguration), 'configuration');

    $commands = [
        new MigrationsCommand\DumpSchemaCommand(),
        new MigrationsCommand\ExecuteCommand(),
        new MigrationsCommand\GenerateCommand(),
        new MigrationsCommand\LatestCommand(),
        new MigrationsCommand\MigrateCommand(),
        new MigrationsCommand\RollupCommand(),
        new MigrationsCommand\StatusCommand(),
        new MigrationsCommand\VersionCommand(),
        new MigrationsCommand\DiffCommand(),
        new MigrationsCommand\UpToDateCommand(),
    ];

    ConsoleRunner::run($helperSet, $commands);
})();
