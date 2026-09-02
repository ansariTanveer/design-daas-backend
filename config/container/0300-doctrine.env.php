<?php

declare(strict_types=1);

return [
    'config.database.dsn' => function (array $server) {
        return \DI\value($server['APPLICATION_DATABASE_DSN'] ?? null);
    },
];
