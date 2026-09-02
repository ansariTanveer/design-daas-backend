<?php

namespace Application\Core\Util\Mail;

use DI\Annotation\Inject;
use Symfony\Component\Console\Output\OutputInterface;

class MailCliCommands
{
    /** @Inject() */
    private MailQueueService $processor;

    /** @Inject() */
    private MailService $mailService;

    /**
     * Send a mail using the default mailer
     *
     * @command test:send-mail
     * @param OutputInterface $output
     * @param string $recipient
     * @return int
     */
    public function sendTestMailAction(OutputInterface $output, string $recipient): int
    {
        if (filter_var($recipient, FILTER_VALIDATE_EMAIL) === null) {
            $output->writeln('Invalid e-mail');
            return 1;
        }

        $this->mailService->enqueueTestMail(
            ['test_placeholder' => 'nuro'],
            $recipient
        );

        $output->writeln('Test mail sent');

        return 0;
    }

    /**
     * Send queued mails
     *
     * Should be run by a cronjob periodically, interval based on the specified timeout
     * to achieve continuous processing.
     *
     * @command mail:send
     * @param OutputInterface $output
     * @param int $timeout Terminate after X seconds (0 = no limit)
     * @return int
     */
    public function processMailQueueAction(OutputInterface $output, int $timeout): int
    {
        if ($timeout < 0) {
            $output->writeln('Invalid timeout');
            return 1;
        }

        $this->processor->processQueue($timeout * 1000);

        return 0;
    }
}
