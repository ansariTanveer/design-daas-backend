<?php

namespace Application\Core\User\Exception;

use Exception;
use Throwable;

final class UserServiceException extends Exception
{
    private function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }


    public static function invalidEmailAddress(): self
    {
        return new self("Invalid e-mail or password");
    }

    public static function invalidPassword(InvalidPasswordException $e): self
    {
        return new self("Invalid e-mail or password", 1, $e);
    }

    public static function invalidAdminId(): self
    {
        return new self('Invalid adminId', 2);
    }

    public static function invalidUserId(): self
    {
        return new self('Invalid userId', 2);
    }

    public static function userAlreadyBelongsToGroup(): self
    {
        return new self('User already belongs to group');
    }

    public static function emailAddressInUse(): self
    {
        return new self("Invalid e-mail or password");
    }

    public static function invalidRegistrationCode(): self
    {
        return new self('Invalid registration code');
    }
}
