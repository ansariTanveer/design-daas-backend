<?php

namespace Application\Core\Util\Mail;

enum MailTypeEnum: string
{
    case TEST_MAIL = 'test_mail';
    case VALIDATE_EMAIL = 'validate_email';
    case PASSWORD_RESET = 'password_reset';
    case APPLICATION_REQUEST = 'application_request';
}
