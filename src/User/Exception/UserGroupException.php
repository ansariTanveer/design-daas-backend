<?php

namespace Application\Core\User\Exception;

use Exception;

final class UserGroupException extends Exception
{
    private function __construct(string $message = "")
    {
        parent::__construct($message);
    }

    public static function userAlreadyBelongsToGroup(): self
    {
        return new self('User already belongs to group');
    }

    public static function userGroupNotFound(): self
    {
        return new self('UserGroup not found');
    }
}
