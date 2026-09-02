<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Exception;

use Exception;

final class PermissionsException extends Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function errorReadingStream(): self
    {
        return new self('Error reading stream for permission JSON data');
    }

    public static function errorParsingJson(): self
    {
        return new self('Error parsing JSON from stream');
    }

    public static function invalidFunctionName(): self
    {
        return new self('Invalid function name');
    }
}
