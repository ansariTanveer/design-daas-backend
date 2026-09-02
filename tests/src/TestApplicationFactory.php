<?php

declare(strict_types=1);

namespace Application\Test;

use Application\Common\Application\ApplicationInterface;
use Application\Common\Application\Cli\CliApplication;
use Application\Common\Application\Http\HttpApplication;
use Application\Core\Main\ApplicationFactory;
use Application\Core\Util\OAuth2\Model\PersistedClient;
use Application\Core\Util\OAuth2\Repository\ClientRepository;
use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;

final class TestApplicationFactory
{
    private function __construct()
    {
    }

    /**
     * @var ArrayObject<string, mixed>
     */
    private static ArrayObject $cache;

    /**
     * @return ArrayObject<string, mixed>
     */
    private static function cache(): ArrayObject
    {
        if (!isset(self::$cache)) {
            self::$cache = new ArrayObject();
        }
        return self::$cache;
    }

    private static function executePrepareApplicationScripts(string $dir, ApplicationInterface $application): void
    {
        $files = scandir($dir);
        assert($files !== false);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (!is_file($path) || !str_ends_with($path, '.php')) {
                continue;
            }
            $callback = (function () use ($path): callable {
                return require $path;
            })();
            $callback($application, self::cache());
        }
    }

    public static function generic(): ApplicationInterface
    {
        $application = ApplicationFactory::generic();
        self::prepareApplication($application);

        return $application;
    }

    public static function cli(): CliApplication
    {
        $application = ApplicationFactory::cli();
        self::prepareApplication($application);
        self::executePrepareApplicationScripts(dirname(__DIR__) . '/prepare-application/cli', $application);

        return $application;
    }

    public static function http(): HttpApplication
    {
        $application = ApplicationFactory::http();
        self::prepareApplication($application);
        self::executePrepareApplicationScripts(dirname(__DIR__) . '/prepare-application/http', $application);

        return $application;
    }

    private static function prepareApplication(ApplicationInterface $application): void
    {
        self::executePrepareApplicationScripts(dirname(__DIR__) . '/prepare-application', $application);
    }

    public static function injectDatabaseConnection(ApplicationInterface $application): void
    {
        $application->container()->set(
            'application.database.connection_factory',
            TestDatabaseConnectionFactory::getRegistered(),
        );
    }

    /**
     * Creates an OAuth2 client using {@link TestApplication::TEST_CLIENT_ID} and
     * {@link TestApplication::TEST_CLIENT_SECRET} as credentials.
     */
    public static function injectOAuth2Configuration(ApplicationInterface $application): void
    {
        $em = self::extractEntityManager($application);
        /** @var ClientRepository $repository */
        $repository = $application->container()->get(ClientRepository::class);

        $client = $repository->getClientEntity(TestCase::TEST_CLIENT_ID);
        if ($client === null) {
            $client = new PersistedClient(
                TestCase::TEST_CLIENT_ID,
                'Test Client',
                [],
                true,
                TestCase::TEST_CLIENT_SECRET
            );
            $em->persist($client);
        }

        $em->flush();
    }

    public static function extractEntityManager(ApplicationInterface $application): EntityManagerInterface
    {
        $factory = $application->container()->get('application.database.connection_factory');
        $dsn = $application->container()->get('config.database.dsn');
        assert(
            $dsn !== null || $factory instanceof TestDatabaseConnectionFactory,
            'No database DSN or test database connection factory, '
            . 'did you forget to call TestApplicationFactory::injectDatabaseConnection()?',
        );
        $em = $application->container()->get(EntityManagerInterface::class);
        assert($em instanceof EntityManagerInterface);
        return $em;
    }
}
