<?php

declare(strict_types=1);

return [
    'config.log.level' => function (array $server) {
        return \DI\value($server['APPLICATION_LOG_LEVEL'] ?? 'info');
    },
    'config.log.stdout' => function (array $server) {
        return \DI\value($server['APPLICATION_LOG_STDOUT'] ?? null);
    },
    'config.log.file' => function (array $server) {
        return \DI\value($server['APPLICATION_LOG_FILE'] ?? null);
    },
];
