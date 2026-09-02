<?php

namespace Application\Core\User\Exception;

use Exception;

final class BaseUserException extends Exception
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
        return new self('User not in group');
    }

    public static function invalidId(?int $id): self
    {
        if (!is_null($id)) {
            return new self('Invalid id ' . $id);
        } else {
            return new self('Invalid id');
        }
    }
}
