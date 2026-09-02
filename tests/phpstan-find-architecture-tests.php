<?php

declare(strict_types=1);

$config = [
    'services' => [],
];

$baseDir = __DIR__ . '/src/Arch/';
foreach ((new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir))) as $file) {
    assert($file instanceof SplFileInfo);
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $name = $file->getPathname();
    // $name = /project/tests/src/Arch/Main/BasicArchitectureTest.php
    $name = substr($file->getPathname(), 0, -strlen($file->getExtension()) - 1);
    // $name = /project/tests/src/Arch/Main/BasicArchitectureTest
    $name = substr($name, strlen($baseDir));
    // $name = Main/BasicArchitectureTest
    $name = 'Application/Test/Arch/' . $name;
    // $name = Application/Test/Arch/Main/BasicArchitectureTest
    $name = str_replace('/', '\\', $name);
    // $name = Application\Test\Arch\Main\BasicArchitectureTest
    $config['services'][] = [
        'class' => $name,
        'tags' => ['phpat.test'],
    ];
}

return $config;
