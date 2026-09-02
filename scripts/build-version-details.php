<?php

declare(strict_types=1);

echo 'Building version details ...' . PHP_EOL;

exec('git log -1 --pretty=format:\'%ct,%h,%H\'', $commitInformationOutput, $exitCode);
if ($exitCode !== 0 || count($commitInformationOutput) < 1) {
    trigger_error(
        sprintf('Failed to retrieve commit information (exitCode = %1$d)', $exitCode),
        E_USER_ERROR,
    );
}
list($timestamp, $shortHash, $longHash) = explode(',', reset($commitInformationOutput), 3);

exec('git rev-list --count ' . $shortHash, $commitCountOutput, $exitCode);
if ($exitCode !== 0 || count($commitCountOutput) < 1) {
    trigger_error(
        sprintf('Failed to retrieve commit count (exitCode = %1$d)', $exitCode),
        E_USER_ERROR,
    );
}
$commitCount = reset($commitCountOutput);

$config = json_decode((string)file_get_contents('composer.json'));
if (!($config instanceof stdClass)) {
    trigger_error(
        'Failed to load composer configuration',
        E_USER_ERROR,
    );
}

exec('git status --porcelain', $workingDirectoryCheckOutput, $exitCode);
$cleanWorkingDirectory = ($exitCode === 0 && count($workingDirectoryCheckOutput) < 1);

$applicationVersion = sprintf(
    '%1$s.%2$d.%3$s%4$s',
    gmdate('Y.m.d', (int)$timestamp),
    $commitCount,
    $shortHash,
    $cleanWorkingDirectory ? '' : '+pdev',
);

file_put_contents(
    dirname(__DIR__) . '/version_details.build',
    json_encode(
        [
            'application_version' => $applicationVersion,
            'build_commit' => $longHash,
            'build_source_clean' => $cleanWorkingDirectory,
            'build_date' => gmdate(
                DateTimeInterface::RFC3339_EXTENDED,
                $cleanWorkingDirectory ? (int)$timestamp : time(),
            ),
        ],
        JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
    ) . PHP_EOL,
);

file_put_contents(
    dirname(__DIR__) . '/version_tag.build',
    str_replace(['/', '+'], ['.', '.'], $applicationVersion),
);
