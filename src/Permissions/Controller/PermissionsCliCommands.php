<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Controller;

use Application\Core\Permissions\Exception\PermissionsException;
use Application\Core\Permissions\Service\PermissionsService;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class PermissionsCliCommands
{
    public function __construct(
        private PermissionsService $permissionsService,
    ) {
    }

    /**
     * Imports permissions from JSON
     *
     * @command permissions:import
     * @param OutputInterface $output
     * @param string $jsonFile
     * @return int
     */
    public function importPermissionsCommand(OutputInterface $output, string $jsonFile): int
    {
        if (!file_exists($jsonFile)) {
            $output->writeln('File not found');
            return 1;
        }

        $stream = fopen($jsonFile, 'r');
        if (!is_resource($stream)) {
            $output->writeln('Failed to open file (permissions missing?)');
            return 1;
        }

        try {
            $this->permissionsService->updatePermissionsFromStream($stream);
        } catch (PermissionsException $exception) {
            $output->writeln($exception->getMessage());
            return 1;
        }

        return 0;
    }
}
