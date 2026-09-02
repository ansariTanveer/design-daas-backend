<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Exception;

use Exception;

final class EndpointGroupUserGroupAccessException extends Exception
{
    private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function invalidEndpointGroupUserGroupAccess(): self
    {
        return new self('Endpoint Group not associated with User Group');
    }
}
