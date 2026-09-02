<?php

declare(strict_types=1);

use Application\Common\DoctrineORM\ConfigurationCacheInterface;
use Application\Common\SymfonyConsole\CommandCacheInterface;
use Application\Common\SymfonyEventDispatcher\EventSubscriberCacheInterface;
use Application\Common\SymfonyRouting\RouteCacheInterface;
use Application\Core\Main\ApplicationFactory;
use Application\Core\Util\Swagger\SwaggerDefinitionCache;

echo 'Building cache ...' . PHP_EOL;

require dirname(__DIR__) . '/config/bootstrap.php';

$cacheDir = dirname(__DIR__) . '/cache.build';
if (!is_dir($cacheDir)) {
    mkdir($cacheDir);
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::CHILD_FIRST,
);
/** @var SplFileInfo $file */
foreach ($files as $file) {
    if ($file->isDir()) {
        rmdir((string)$file->getRealPath());
    } else {
        unlink((string)$file->getRealPath());
    }
}

$genericApplication = ApplicationFactory::generic();
$genericApplication->container()->set('application.runtime.cache_enabled', true);
$genericApplication->container()->set('application.runtime.cache_dir', $cacheDir);
$cliApplication = ApplicationFactory::cli();
$cliApplication->container()->set('application.runtime.cache_enabled', true);
$cliApplication->container()->set('application.runtime.cache_dir', $cacheDir);
$httpApplication = ApplicationFactory::http();
$httpApplication->container()->set('application.runtime.cache_enabled', true);
$httpApplication->container()->set('application.runtime.cache_dir', $cacheDir);

$doctrineConfigurationCache = $genericApplication->container()->get('application.database.configuration_cache');
assert($doctrineConfigurationCache instanceof ConfigurationCacheInterface);
$doctrineConfigurationCache->buildCache();

$eventSubscriberCache = $genericApplication->container()->get('application.event_dispatcher.subscriber_cache');
assert($eventSubscriberCache instanceof EventSubscriberCacheInterface);
$eventSubscriberCache->buildCache();

$cliCommandCache = $cliApplication->container()->get('application.dispatcher.command_cache');
assert($cliCommandCache instanceof CommandCacheInterface);
$cliCommandCache->buildCache();

$httpRouteCache = $httpApplication->container()->get('application.dispatcher.route_cache');
assert($httpRouteCache instanceof RouteCacheInterface);
$httpRouteCache->buildCache();

$swaggerCache = $httpApplication->container()->get(SwaggerDefinitionCache::class);
assert($swaggerCache instanceof SwaggerDefinitionCache);
$swaggerCache->buildCache();
