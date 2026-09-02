<?php

declare(strict_types=1);

namespace Application\Core\Util\Mail;

use Assert\InvalidArgumentException;

final readonly class Mail
{
    public function __construct(
        private MailTypeEnum $type,
        private string $recipientMail,
        private string $subject,
        private string $body,
        private string $senderMail,
        private string $senderName
    ) {
        assert(filter_var($this->recipientMail, FILTER_VALIDATE_EMAIL));
        assert(filter_var($this->senderMail, FILTER_VALIDATE_EMAIL));
    }

    public function type(): MailTypeEnum
    {
        return $this->type;
    }

    public function recipientMail(): string
    {
        return $this->recipientMail;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function senderMail(): string
    {
        return $this->senderMail;
    }

    public function senderName(): string
    {
        return $this->senderName;
    }

    public function jsonSerialized(): string
    {
        $serialized = json_encode([
            'type' => $this->type->value,
            'recipient' => $this->recipientMail,
            'subject' => $this->subject,
            'body' => $this->body,
            'senderMail' => $this->senderMail,
            'senderName' => $this->senderName
        ]);
        assert(is_string($serialized));

        return $serialized;
    }

    /**
     * @param string $json
     * @return Mail
     * @throws MailerRuntimeException
     */
    public static function jsonDeserialize(string $json): Mail
    {
        try {
            $deserialized = json_decode($json, true, 16);

            /** @var array{
             *     type: string,
             *     recipient: string,
             *     subject: string,
             *     body: string,
             *     senderMail: string,
             *     senderName: string
             *  } $deserialized
             */
            assert(is_array($deserialized));
            assert(array_key_exists('type', $deserialized));
            assert(array_key_exists('recipient', $deserialized));
            assert(array_key_exists('subject', $deserialized));
            assert(array_key_exists('body', $deserialized));
            assert(is_string($deserialized['type']));
            assert(is_string($deserialized['recipient']));
            assert(is_string($deserialized['subject']));
            assert(is_string($deserialized['body']));
            assert(is_string($deserialized['senderMail']));
            assert(is_string($deserialized['senderName']));

            $mailType = MailTypeEnum::tryFrom($deserialized['type']);
            assert($mailType instanceof MailTypeEnum);

            return new self(
                $mailType,
                $deserialized['recipient'],
                $deserialized['subject'],
                $deserialized['body'],
                $deserialized['senderMail'],
                $deserialized['senderName']
            );
        } catch (InvalidArgumentException $invalidArgumentException) {
            throw MailerRuntimeException::deserializationError($invalidArgumentException);
        }
    }
}
