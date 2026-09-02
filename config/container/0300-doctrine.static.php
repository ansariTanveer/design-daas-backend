<?php

declare(strict_types=1);

use Application\Common\DoctrineDBAL\CallbackConnectionPreparer;
use Application\Common\DoctrineDBAL\DatabaseConnectionFactory;
use Application\Common\DoctrineORM\ConfigurationCache;
use Application\Common\DoctrineORM\ConfigurationCacheEntityListenerResolverInjector;
use Application\Common\DoctrineORM\ConfigurationCacheInterface;
use Application\Common\DoctrineORM\ConfigurationCollector;
use Application\Common\DoctrineORM\ConfigurationCollectorInterface;
use Application\Common\DoctrineORM\ContainerAwareEntityListenerResolver;
use Application\Common\DoctrineORM\LazyEntityManager;
use Application\Common\InternalMessageBroker\DbalInternalMessageBroker;
use Application\Common\InternalMessageBroker\InternalMessageBrokerInterface;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\AbstractAsset;
use Doctrine\Migrations\Configuration\Configuration as DoctrineMigrationsConfiguration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;

return [
    'application.database.ignored_tables' => [
        'enqueue',
    ],
    'application.database.migrations_dir' => dirname(__DIR__, 2) . '/migrations',
    'application.database.migrations_table' => 'migrations',
    'application.database.migrations_namespace' => 'Application\\Migrations',
    'application.database.migrations_check_database_platform' => false,
    'application.database.migrations_configuration' => \DI\factory(
        function (ContainerInterface $container): DoctrineMigrationsConfiguration {
            $em = $container->get('application.database.entity_manager');
            assert($em instanceof EntityManagerInterface);
            $migrationsDirectory = $container->get('application.database.migrations_dir');
            assert(is_string($migrationsDirectory));
            $migrationsTable = $container->get('application.database.migrations_table');
            assert(is_string($migrationsTable));
            $migrationsNamespace = $container->get('application.database.migrations_namespace');
            assert(is_string($migrationsNamespace));
            $checkDatabasePlatform = $container->get('application.database.migrations_check_database_platform');
            assert(is_bool($checkDatabasePlatform));
            $connection = $em->getConnection();
            $configuration = new DoctrineMigrationsConfiguration($connection);
            $configuration->setMigrationsDirectory($migrationsDirectory);
            $configuration->setMigrationsTableName($migrationsTable);
            $configuration->setMigrationsNamespace($migrationsNamespace);
            $configuration->setCheckDatabasePlatform($checkDatabasePlatform);
            return $configuration;
        },
    ),
    'application.database.configuration_collector' => \DI\factory(
        function (ContainerInterface $container): ConfigurationCollectorInterface {
            $collector = new ConfigurationCollector();
            $collector->addSearchPath(dirname(__DIR__, 2) . '/src/');
            return $collector;
        },
    ),
    'application.database.configuration_cache' => \DI\factory(
        function (ContainerInterface $container): ConfigurationCacheInterface {
            $collector = $container->get('application.database.configuration_collector');
            assert($collector instanceof ConfigurationCollectorInterface);
            $useCache = $container->get('application.runtime.cache_enabled');
            assert(is_bool($useCache));
            $cacheDir = $container->get('application.runtime.cache_dir');
            assert($cacheDir === null || is_string($cacheDir));
            $configurationCache = new ConfigurationCache(
                $collector,
                $useCache ?
                    $cacheDir :
                    null,
                'doctrine-configuration.cache',
            );
            $resolver = new ContainerAwareEntityListenerResolver($container);
            return new ConfigurationCacheEntityListenerResolverInjector($configurationCache, $resolver);
        },
    ),
    'application.database.configuration' => \DI\factory(
        function (ContainerInterface $container): Configuration {
            $ignoredTables = $container->get('application.database.ignored_tables');
            assert(is_array($ignoredTables));
            $configuration = new Configuration();
            $configuration->setSchemaAssetsFilter(
                function (string | AbstractAsset $assetName) use ($ignoredTables): bool {
                    if ($assetName instanceof AbstractAsset) {
                        $assetName = $assetName->getName();
                    }
                    return !in_array($assetName, $ignoredTables, true);
                },
            );
            return $configuration;
        },
    ),
    'application.database.event_manager' => \DI\autowire(EventManager::class),
    'application.database.connection_preparer.prepare_params_callback' => \DI\factory(
        function (ContainerInterface $container): callable {
            return function (array $params): array {
                $additionalDefaultTableOptions = array_merge(
                    $params['defaultTableOptions'] ?? [],
                    [
                        'engine' => 'InnoDB',
                        'row_format' => 'DYNAMIC',
                        'charset' => 'utf8mb4',
                        'collate' => 'utf8mb4_bin',
                    ],
                );
                $additionalParams = [
                    'charset' => 'utf8mb4',
                    'collate' => 'utf8mb4_bin',
                    'defaultTableOptions' => $additionalDefaultTableOptions,
                ];
                return array_merge(
                    $params,
                    $additionalParams,
                );
            };
        },
    ),
    'application.database.connection_preparer.prepare_configuration_callback' => \DI\factory(
        function (ContainerInterface $container): callable {
            return function () use ($container): Configuration {
                $configuration = $container->get('application.database.configuration');
                assert($configuration instanceof Configuration);
                return $configuration;
            };
        },
    ),
    'application.database.connection_preparer.prepare_event_manager_callback' => \DI\factory(
        function (ContainerInterface $container): callable {
            return function () use ($container): EventManager {
                $eventManager = $container->get('application.database.event_manager');
                assert($eventManager instanceof EventManager);
                return $eventManager;
            };
        },
    ),
    'application.database.connection_preparer.prepare_connection_callback' => \DI\factory(
        function (ContainerInterface $container): callable {
            return function (Connection $connection): void {
            };
        },
    ),
    'application.database.connection_preparer' => \DI\autowire(CallbackConnectionPreparer::class)
        ->constructorParameter(0, \DI\get('application.database.connection_preparer.prepare_params_callback'))
        ->constructorParameter(1, \DI\get('application.database.connection_preparer.prepare_configuration_callback'))
        ->constructorParameter(2, \DI\get('application.database.connection_preparer.prepare_event_manager_callback'))
        ->constructorParameter(3, \DI\get('application.database.connection_preparer.prepare_connection_callback')),
    'application.database.connection_factory' => \DI\autowire(DatabaseConnectionFactory::class)
        ->constructorParameter(0, \DI\get('config.database.dsn'))
        ->constructorParameter(1, \DI\get('application.database.connection_preparer')),
    'application.database.entity_manager' => \DI\autowire(LazyEntityManager::class)
        ->constructorParameter(0, \DI\get('application.database.connection_factory'))
        ->constructorParameter(1, \DI\get('application.database.configuration_cache')),
    'application.database.internal_message_broker' => \DI\factory(
        function (ContainerInterface $container): InternalMessageBrokerInterface {
            $em = $container->get(EntityManagerInterface::class);
            assert($em instanceof EntityManagerInterface);
            return DbalInternalMessageBroker::fromEntityManager('enqueue', $em);
        },
    ),
    EntityManagerInterface::class => \DI\get('application.database.entity_manager'),
    InternalMessageBrokerInterface::class => \DI\get('application.database.internal_message_broker'),
];
