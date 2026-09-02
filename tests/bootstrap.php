<?php

declare(strict_types=1);

// ===== environment setup =====

require dirname(__DIR__) . '/config/bootstrap.php';

\Application\Test\TestDatabaseConnectionFactory::registerMySQL($_SERVER['TEST_DATABASE_DSN']);

// ===== prepare test run =====

\Application\Test\TestCase::clearTestTmpFiles();
