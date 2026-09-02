<?php

declare(strict_types=1);

use Application\Common\Application\ApplicationInterface;
use Application\Test\TestCase;

return function (ApplicationInterface $application, ArrayObject $cache): void {
    $application->container()->set(
        'config.oauth2.private_key_file',
        dirname(__DIR__) . '/assets/oauth2.key'
    );

    $application->container()->set(
        'config.oauth2.public_key_file',
        dirname(__DIR__) . '/assets/oauth2.pub'
    );

    $application->container()->set(
        'config.oauth2.encryption_key_file',
        dirname(__DIR__) . '/assets/oauth2.enc'
    );

    $application->container()->set(
        'config.oauth2.refresh_token_ttl_in_seconds',
        86400
    );

    $application->container()->set(
        'config.oauth2.access_token_ttl_in_seconds',
        86400
    );

    $application->container()->set(
        'config.oauth2.user_test_token_prefix',
        TestCase::USER_TEST_TOKEN_PREFIX
    );
};
