<?php

declare(strict_types=1);

namespace Application\Core\Util\Mail;

use Assert\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Throwable;

final class MailerRuntimeException extends RuntimeException
{
    private function __construct(
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null,
        private readonly ?Mail $offendingMail = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function log(LoggerInterface $logger): void
    {
        if (!is_null($this->offendingMail)) {
            $logger->error(
                $this->getMessage(),
                [
                    'type' => $this->offendingMail->type()->value,
                    'recipient' => $this->offendingMail->recipientMail(),
                    'exception' => $this,
                ]
            );
        } else {
            $logger->error(
                $this->getMessage(),
                [
                    'exception' => $this,
                ]
            );
        }
    }

    public static function deserializationError(InvalidArgumentException $invalidArgumentException): self
    {
        return new self(
            'Failed to deserialize mail - ' . $invalidArgumentException->getMessage(),
            0,
            $invalidArgumentException
        );
    }

    public static function transportFailed(TransportExceptionInterface $transportException, Mail $mail): self
    {
        return new self(
            'An error occurred sending mail - ' . $transportException->getMessage(),
            1,
            $transportException,
            $mail
        );
    }

    public static function recipientInvalidEmail(string $email): self
    {
        return new self(
            sprintf('Invalid E-Mail adress for recipient (%1$s)', $email),
            2
        );
    }

    public static function bodyTemplateNotFound(MailTypeEnum $mailTypeEnum): self
    {
        return new self(
            sprintf('Template for body for email type %1$s not found', $mailTypeEnum->value),
            3
        );
    }

    public static function subjectTemplateNotFound(MailTypeEnum $mailTypeEnum): self
    {
        return new self(
            sprintf('Template for subject for email type %1$s not found', $mailTypeEnum->value),
            4
        );
    }

    public static function unhandledExceptionDuringMailSend(Throwable $e, ?Mail $mail): self
    {
        return new self(
            sprintf(
                'Unhandled exception while sending %1$s: %2$s',
                is_null($mail) ? '(unknown)' : $mail->subject(),
                $e->getMessage()
            ),
            5
        );
    }

    public static function templateNotAvailable(
        MailTypeEnum $mailTypeEnum,
        MailLanguageEnum $languageEnum
    ): self {
        return new self(
            sprintf(
                'No template for mail type "%1$s" in language "%2$s" found',
                $mailTypeEnum->value,
                $languageEnum->value
            ),
            6
        );
    }
}
