<?php

declare(strict_types=1);

use Application\Common\SymfonyEventDispatcher\EventSubscriberCache;
use Application\Common\SymfonyEventDispatcher\EventSubscriberCacheInterface;
use Application\Common\SymfonyEventDispatcher\EventSubscriberCollector;
use Application\Common\SymfonyEventDispatcher\EventSubscriberCollectorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

return [
    'application.event_dispatcher.subscriber_collector' => \DI\factory(
        function (ContainerInterface $container): EventSubscriberCollectorInterface {
            $collector = new EventSubscriberCollector();
            $collector->addSearchPath(
                dirname(__DIR__, 2) . '/src/',
                '*EventSubscriber.php',
            );
            return $collector;
        },
    ),
    'application.event_dispatcher.subscriber_cache' => \DI\factory(
        function (ContainerInterface $container): EventSubscriberCacheInterface {
            $collector = $container->get('application.event_dispatcher.subscriber_collector');
            assert($collector instanceof EventSubscriberCollectorInterface);
            $useCache = $container->get('application.runtime.cache_enabled');
            assert(is_bool($useCache));
            $cacheDir = $container->get('application.runtime.cache_dir');
            assert($cacheDir === null || is_string($cacheDir));
            return new EventSubscriberCache(
                $collector,
                $useCache ?
                    sprintf('%1$s/event-subscribers.cache', $cacheDir) :
                    null,
            );
        },
    ),
    EventDispatcherInterface::class => \DI\decorate(
        function (EventDispatcherInterface $previous, ContainerInterface $container): EventDispatcherInterface {
            $subscriberCache = $container->get('application.event_dispatcher.subscriber_cache');
            assert($subscriberCache instanceof EventSubscriberCacheInterface);
            foreach ($subscriberCache->loadEventSubscriberClassNames() as $className) {
                $subscriber = $container->get($className);
                assert($subscriber instanceof EventSubscriberInterface);
                $previous->addSubscriber($subscriber);
            }
            return $previous;
        },
    ),
];
