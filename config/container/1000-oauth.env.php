<?php

declare(strict_types=1);

return [
    'config.oauth2.private_key_file' => function (array $server) {
        return \DI\value($server['OAUTH_PRIVATE_KEY_FILE'] ?? null);
    },
    'config.oauth2.public_key_file' => function (array $server) {
        return \DI\value($server['OAUTH_PUBLIC_KEY_FILE'] ?? null);
    },
    'config.oauth2.encryption_key_file' => function (array $server) {
        return \DI\value($server['OAUTH_ENCRYPTION_KEY_FILE'] ?? null);
    },
    'config.oauth2.access_token_ttl_in_seconds' => function (array $server) {
        return \DI\value($server['OAUTH_ACCESS_TOKEN_TTL'] ?? 86400);
    },
    'config.oauth2.refresh_token_ttl_in_seconds' => function (array $server) {
        return \DI\value($server['OAUTH_REFRESH_TOKEN_TTL'] ?? 86400);
    },
];
