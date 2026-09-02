<?php

namespace Application\Test\Integration\Util;

use Application\Core\Util\Mail\Mail;
use Application\Core\Util\Mail\MailLanguageEnum;
use Application\Core\Util\Mail\MailQueueService;
use Application\Core\Util\Mail\MailService;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class MailGenerationAndEnqueueTest extends TestCase
{
    /** @var MailQueueService|MockObject */
    private MailQueueService|MockObject $mockMailQueueService;

    /** @var MailService */
    private MailService $mailService;

    protected function setUp(): void
    {
        parent::setUp();

        $application = TestApplicationFactory::generic();

        $this->mockMailQueueService = $this->createMock(MailQueueService::class);
        $application->container()->set(MailQueueService::class, $this->mockMailQueueService);

        $mailService = $application->container()->get(MailService::class);
        assert($mailService instanceof MailService);
        $this->mailService = $mailService;
    }

    public function testGeneratesValidateMail(): void
    {
        $this->mockMailQueueService
            ->expects(self::once())
            ->method('enqueue')
            ->willReturnCallback(function (Mail $mail): void {

                self::assertEquals('john.doe@example.com', $mail->recipientMail());
                self::assertEquals('Validate your e-mail address', $mail->subject());
                self::assertEquals('dummy.sender@example.com', $mail->senderMail());
                self::assertEquals('Dummy sender', $mail->senderName());

                $body = $mail->body();
                self::assertStringContainsString('ABC1234', $body);
                self::assertStringContainsString('Please confirm your e-Mail', $body);
            });

        $this->mailService->enqueueValidateEmail(
            ['registration_code' => 'ABC1234'],
            'john.doe@example.com'
        );
    }

    public function testGeneratesTestMail(): void
    {
        $this->mockMailQueueService
            ->expects(self::once())
            ->method('enqueue')
            ->willReturnCallback(function (Mail $mail): void {
                self::assertEquals('john.doe@example.com', $mail->recipientMail());
                self::assertEquals('Test e-mail from DESIGN.', $mail->subject());
                self::assertEquals('dummy.sender@example.com', $mail->senderMail());
                self::assertEquals('Dummy sender', $mail->senderName());

                $body = $mail->body();
                self::assertStringContainsString('This is a test e-mail.', $body);
                self::assertStringContainsString('alenda lux ubi orta libertas', $body);
            });

        $this->mailService->enqueueTestMail(
            [
                'test_placeholder' => 'alenda lux ubi orta libertas',
            ],
            'john.doe@example.com'
        );
    }

    public function testGeneratesApplicationRequestEmailDeu(): void
    {
        $this->mockMailQueueService
            ->expects(self::once())
            ->method('enqueue')
            ->willReturnCallback(function (Mail $mail): void {
                self::assertEquals('john.doe@example.com', $mail->recipientMail());
                self::assertEquals('Anfrage zur Freischaltung', $mail->subject());
                self::assertEquals('dummy.sender@example.com', $mail->senderMail());
                self::assertEquals('Dummy sender', $mail->senderName());

                $body = $mail->body();

                // check for a word only in the german template
                self::assertStringContainsString('Anfrage', $body);

                self::assertStringContainsString('my_user_name', $body);
                self::assertStringContainsString('my_user_id', $body);
                self::assertStringContainsString('my_application', $body);
            });

        $this->mailService->enqueueApplicationRequestEmail(
            [
                'user_name' => 'my_user_name',
                'user_id' => 'my_user_id',
                'application' => 'my_application',
            ],
            'john.doe@example.com',
            MailLanguageEnum::DEU
        );
    }

    public function testGeneratesApplicationRequestEmailEng(): void
    {
        $this->mockMailQueueService
            ->expects(self::once())
            ->method('enqueue')
            ->willReturnCallback(function (Mail $mail): void {
                self::assertEquals('john.doe@example.com', $mail->recipientMail());
                self::assertEquals('User access request', $mail->subject());
                self::assertEquals('dummy.sender@example.com', $mail->senderMail());
                self::assertEquals('Dummy sender', $mail->senderName());

                $body = $mail->body();

                // check for a word only in the english template
                self::assertStringContainsString('regard', $body);

                self::assertStringContainsString('my_user_name', $body);
                self::assertStringContainsString('my_user_id', $body);
                self::assertStringContainsString('my_application', $body);
            });

        $this->mailService->enqueueApplicationRequestEmail(
            [
                'user_name' => 'my_user_name',
                'user_id' => 'my_user_id',
                'application' => 'my_application',
            ],
            'john.doe@example.com'
        );
    }
}
