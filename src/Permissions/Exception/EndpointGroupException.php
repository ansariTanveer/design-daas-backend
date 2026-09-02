<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Exception;

use Exception;

final class EndpointGroupException extends Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function endpointGroupNotFound(): self
    {
        return new self('EndpointGroup not found');
    }
}
