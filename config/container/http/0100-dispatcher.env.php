<?php

declare(strict_types=1);

return [
    'config.dispatcher.trust_proxy_headers' => function (array $server) {
        return \DI\value($server['APPLICATION_TRUST_PROXY_HEADERS'] ?? null);
    },
];
