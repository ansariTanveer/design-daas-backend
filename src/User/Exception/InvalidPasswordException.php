<?php

namespace Application\Core\User\Exception;

use InvalidArgumentException;

class InvalidPasswordException extends InvalidArgumentException implements PasswordExceptionInterface
{
    private const MESSAGE = "Password needs at least 8 letters with at least 1 uppercase letter and 1 punctuation mark";

    public function __construct()
    {
        parent::__construct(self::MESSAGE);
    }
}
