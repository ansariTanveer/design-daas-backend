<?php

declare(strict_types=1);

namespace Application\Core\Util\Mail;

use DI\Annotation\Inject;

final readonly class MailTemplateService
{
    /**
     * @Inject({
     *     "templateDir" = "config.mail.template_dir",
     * })
     */
    public function __construct(
        private string $templateDir
    ) {
    }

    /**
     * @param array<string, string> $placeholders
     * @throws MailerRuntimeException
     */
    public function mailFromTemplate(
        MailTypeEnum $mailType,
        MailLanguageEnum $mailLanguage,
        array $placeholders,
        string $recipientMail,
        string $senderMail,
        string $senderName
    ): Mail {
        if (filter_var($recipientMail, FILTER_VALIDATE_EMAIL) === false) {
            throw MailerRuntimeException::recipientInvalidEmail($recipientMail);
        }

        $subject = $this->replacePlaceholders($this->subjectTemplate($mailType, $mailLanguage), $placeholders);
        $body = $this->replacePlaceholders($this->bodyTemplate($mailType, $mailLanguage), $placeholders);

        return new Mail(
            $mailType,
            $recipientMail,
            $subject,
            $body,
            $senderMail,
            $senderName
        );
    }

    /**
     * @throws MailerRuntimeException
     */
    private function bodyTemplate(MailTypeEnum $mailType, MailLanguageEnum $mailLanguage): string
    {
        $body = file_get_contents($this->getTemplatePath($mailType, $mailLanguage, 'body.html'));
        if ($body === false) {
            throw MailerRuntimeException::bodyTemplateNotFound($mailType);
        }

        return $body;
    }

    /**
     * @throws MailerRuntimeException
     */
    private function subjectTemplate(MailTypeEnum $mailType, MailLanguageEnum $mailLanguage): string
    {
        $body = file_get_contents($this->getTemplatePath($mailType, $mailLanguage, 'subject.txt'));
        if ($body === false) {
            throw MailerRuntimeException::subjectTemplateNotFound($mailType);
        }

        return $body;
    }

    /**
     * @param array<string, string> $placeholders
     */
    private function replacePlaceholders(string $subject, array $placeholders): string
    {
        $search = [];
        $replace = [];
        foreach ($placeholders as $key => $value) {
            $search[] = '%' . $key . '%';
            $replace[] = $value;
        }

        return str_replace($search, $replace, trim($subject));
    }

    private function getTemplatePath(
        MailTypeEnum $mailType,
        MailLanguageEnum $mailLanguage,
        string $part
    ): string {
        $path = sprintf(
            '%1$s/%2$s/%3$s.%4$s',
            $this->templateDir,
            $mailLanguage->value,
            $mailType->value,
            $part
        );

        if (!file_exists($path)) {
            throw MailerRuntimeException::templateNotAvailable($mailType, $mailLanguage);
        }

        return $path;
    }
}
