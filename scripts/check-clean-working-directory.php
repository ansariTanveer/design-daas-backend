<?php

declare(strict_types=1);

exec('git status --porcelain', $workingDirectoryCheckOutput, $exitCode);
if ($exitCode !== 0 || count($workingDirectoryCheckOutput) > 0) {
    trigger_error(
        sprintf('Working directory not clean (exitCode = %1$d)', $exitCode),
        E_USER_ERROR,
    );
}

echo 'Working directory is clean';
