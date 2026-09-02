<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

(function () {
    $dir = __DIR__ . '/bootstrap';
    $files = scandir($dir);
    assert($files !== false);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (!is_file($path) || !str_ends_with($path, '.php')) {
            continue;
        }
        (function () use ($path): void {
            require $path;
        })();
    }
})();
