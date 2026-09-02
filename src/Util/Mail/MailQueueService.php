<?php

namespace Application\Core\Util\Mail;

use Application\Common\InternalMessageBroker\InternalMessageBrokerInterface;
use Application\Common\InternalMessageBroker\ProcessingResult;
use DI\Annotation\Inject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class MailQueueService
{
    private const MAIL_QUEUE_ID = 'email';

    private ?TransportInterface $mailer;

    /**
     * @Inject({
     *     "internalMessageBroker" = "application.database.internal_message_broker",
     *     "mailerDsn" = "config.mailer.dsn",
     * })
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly InternalMessageBrokerInterface $internalMessageBroker,
        ?string $mailerDsn
    ) {
        $this->mailer = isset($mailerDsn) ? Transport::fromDsn($mailerDsn) : null;
    }

    public function enqueue(Mail $mail): void
    {
        // Use MaiLDebugHelper to evaluate what was enqueued on test
        $this->internalMessageBroker->sendMessage(
            self::MAIL_QUEUE_ID,
            $mail->jsonSerialized()
        );
    }

    public function processQueue(int $timeout): void
    {
        $this->internalMessageBroker->receiveMessages(
            self::MAIL_QUEUE_ID,
            function (string $mailSerialized): ProcessingResult {
                try {
                    $mail = null;
                    try {
                        $mail = Mail::jsonDeserialize($mailSerialized);
                        $this->sendMail($mail);

                        $this->logger->info(
                            'Mail sent',
                            [
                                'type' => $mail->type()->value,
                                'recipient' => $mail->recipientMail(),
                            ]
                        );

                        return ProcessingResult::acknowledge();
                    } catch (Throwable $e) {
                        throw MailerRuntimeException::unhandledExceptionDuringMailSend($e, $mail);
                    }
                } catch (MailerRuntimeException $e) {
                    $e->log($this->logger);
                    return ProcessingResult::rejectAndDiscard();
                }
            },
            $timeout
        );
    }

    /**
     * @throws MailerRuntimeException
     */
    private function sendMail(Mail $mail): void
    {
        assert(
            $this->mailer instanceof TransportInterface,
            'Attempted to (actually) send a mail while config.mailer.dsn is not configured'
        );

        try {
            $message = (new Email())
                ->from(new Address($mail->senderMail(), $mail->senderName()))
                ->to(new Address($mail->recipientMail()))
                ->subject($mail->subject())
                ->html($mail->body());

            $this->mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            throw MailerRuntimeException::transportFailed($e, $mail);
        }
    }
}
