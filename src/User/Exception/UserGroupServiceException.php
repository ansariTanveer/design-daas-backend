<?php

namespace Application\Core\User\Exception;

use Exception;

final class UserGroupServiceException extends Exception
{
    private function __construct(string $message = "")
    {
        parent::__construct($message);
    }

    public static function userGroupNotFound(): self
    {
        return new self("User group not found");
    }

    public static function descriptionCannotBeEmpty(): self
    {
        return new self('Description cannot be empty');
    }
}
