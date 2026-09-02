<?php

declare(strict_types=1);

use Application\Common\HttpBaseUrl\BaseUrlResolverInterface;
use Application\Common\HttpBaseUrl\StaticBaseUrlResolver;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\UriFactoryInterface;

$versionDetailsFile = dirname(__DIR__, 2) . '/config/version_details.json';
$versionDetailsExist = file_exists($versionDetailsFile);
$versionDetails = array_merge(
    (array)require dirname(__DIR__, 2) . '/config/version_details.php',
    $versionDetailsExist ?
        (array)json_decode((string)file_get_contents($versionDetailsFile)) :
        [],
);

return [
    'application.runtime.cache_enabled' => $versionDetailsExist,
    'application.runtime.cache_dir' => dirname(__DIR__, 2) . '/cache',
    'application.runtime.version_details' => $versionDetails,
    BaseUrlResolverInterface::class => \DI\factory(
        function (ContainerInterface $container): BaseUrlResolverInterface {
            $uriFactory = $container->get(UriFactoryInterface::class);
            assert($uriFactory instanceof UriFactoryInterface);
            return new StaticBaseUrlResolver($uriFactory->createUri());
        },
    ),
];
