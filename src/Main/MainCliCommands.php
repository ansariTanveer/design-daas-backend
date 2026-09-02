<?php

declare(strict_types=1);

namespace Application\Core\Main;

use Application\Core\Util\Main\CleanupEvent;
use Application\Core\Util\Main\VersionDetails;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Output\OutputInterface;

final readonly class MainCliCommands
{
    public function __construct(
        private VersionDetails $versionDetails,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * Show the application version
     *
     * @command version
     */
    public function versionCommand(OutputInterface $output): int
    {
        $output->writeln(
            sprintf(
                'Application version: %1$s',
                $this->versionDetails->applicationVersion(),
            ),
        );
        $output->writeln('');
        $output->writeln(
            (string)json_encode(
                $this->versionDetails,
                JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT,
            ),
        );
        return 0;
    }

    /**
     * Delete obsolete data and perform regular maintenance tasks
     *
     * @command maintenance:cleanup
     */
    public function cleanupCommand(OutputInterface $output): int
    {
        $output->writeln('Sending cleanup event');
        $this->eventDispatcher->dispatch(new CleanupEvent());
        $output->writeln('Event processing finished');
        return 0;
    }
}
