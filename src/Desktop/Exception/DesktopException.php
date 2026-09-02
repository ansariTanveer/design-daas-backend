<?php

declare(strict_types=1);

namespace Application\Core\Desktop\Exception;

use Exception;

final class DesktopException extends Exception
{
    private function __construct(string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
    }

    public static function alreadyInGroup(): self
    {
        return new self('Desktop is already in desktop group', 0);
    }

    public static function notInGroup(): self
    {
        return new self('Desktop is not in desktop group', 1);
    }

    public static function notFound(int $id): self
    {
        return new self('Desktop not found: ' . $id, 2);
    }
}
