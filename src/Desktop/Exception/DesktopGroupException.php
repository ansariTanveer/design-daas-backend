<?php

declare(strict_types=1);

namespace Application\Core\Desktop\Exception;

use Exception;

final class DesktopGroupException extends Exception
{
    private function __construct(string $message = "")
    {
        parent::__construct($message);
    }

    public static function userGroupAlreadyInDesktopGroup(): self
    {
        return new self("User Group is already in Desktop Group");
    }

    public static function userGroupNotInDesktopGroup(): self
    {
        return new self("User Group is not in Desktop Group");
    }

    public static function notFound(int $id): self
    {
        return new self('Desktop group not found: ' . $id);
    }
}
