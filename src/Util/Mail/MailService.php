<?php

declare(strict_types=1);

namespace Application\Core\Util\Mail;

use DI\Annotation\Inject;

/** @final */
readonly class MailService
{
    /**
     * @Inject({
     *     "fromMail" = "config.mailer.from_email",
     *     "fromName" = "config.mailer.from_name",
     * })
     */
    public function __construct(
        private MailTemplateService $mailTemplateService,
        private MailQueueService $mailQueueService,
        private string $fromMail,
        private string $fromName
    ) {
    }

    /**
     * @param array{
     *     registration_code: string
     * } $placeholders
     * @throws MailerRuntimeException
     */
    public function enqueueValidateEmail(
        array $placeholders,
        string $email,
        MailLanguageEnum $mailLanguage = MailLanguageEnum::ENG,
    ): void {
        $this->enqueueMailFromTemplate(
            MailTypeEnum::VALIDATE_EMAIL,
            $mailLanguage,
            $placeholders,
            $email
        );
    }

    /**
     * @param array{
     *     test_placeholder: string,
     * } $placeholders
     * @throws MailerRuntimeException
     */
    public function enqueueTestMail(
        array $placeholders,
        string $email,
        MailLanguageEnum $mailLanguage = MailLanguageEnum::ENG
    ): void {
        $this->enqueueMailFromTemplate(
            MailTypeEnum::TEST_MAIL,
            $mailLanguage,
            $placeholders,
            $email
        );
    }

    /**
     * @param array{
     *     user_name: string,
     *     reset_code: string
     * } $placeholders
     * @throws MailerRuntimeException
     */
    public function enqueuePasswordResetCodeEmail(
        array $placeholders,
        string $email,
        MailLanguageEnum $mailLanguage = MailLanguageEnum::ENG
    ): void {
        $this->enqueueMailFromTemplate(
            MailTypeEnum::PASSWORD_RESET,
            $mailLanguage,
            $placeholders,
            $email
        );
    }

    /**
     * @param array{
     *     user_name: non-empty-string,
     *     user_id: non-empty-string,
     *     application: non-empty-string
     * } $placeholders
     * @throws MailerRuntimeException
     */
    public function enqueueApplicationRequestEmail(
        array $placeholders,
        string $email,
        MailLanguageEnum $mailLanguage = MailLanguageEnum::ENG,
    ): void {
        $this->enqueueMailFromTemplate(
            MailTypeEnum::APPLICATION_REQUEST,
            $mailLanguage,
            $placeholders,
            $email
        );
    }

    /**
     * @param array<string, string> $placeholders
     * @throws MailerRuntimeException
     */
    private function enqueueMailFromTemplate(
        MailTypeEnum $mailTypeEnum,
        MailLanguageEnum $mailLanguage,
        array $placeholders,
        string $email
    ): void {
        $mail = $this->mailTemplateService->mailFromTemplate(
            $mailTypeEnum,
            $mailLanguage,
            $placeholders,
            $email,
            $this->fromMail,
            $this->fromName
        );

        $this->mailQueueService->enqueue($mail);
    }
}
