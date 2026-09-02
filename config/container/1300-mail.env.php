<?php

declare(strict_types=1);

return [
    'config.mail.template_dir' => function (array $server) {
        return \DI\value(
            (($server['MAIL_TEMPLATE_DIR'] ?? '') === "")
                ? dirname(__DIR__, 2) . '/assets/mail'
                : $server['MAIL_TEMPLATE_DIR'],
        );
    },
    'config.mailer.dsn' => function (array $server) {
        return \DI\value($server['MAILER_DSN'] ?? null);
    },
    'config.mailer.from_email' => function (array $server) {
        return \DI\value($server['MAILER_FROM_EMAIL'] ?? null);
    },
    'config.mailer.from_name' => function (array $server) {
        return \DI\value($server['MAILER_FROM_NAME'] ?? null);
    },
];
