<?php

declare(strict_types=1);

namespace Application\Core\OAuth2\Exception;

use RuntimeException;
use Throwable;

class OAuth2AuthorizationServerException extends RuntimeException
{
    private function __construct(string $message = "", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function failedToGenerateCryptKey(Throwable $innerException): self
    {
        return new self(
            'Failed to generate cryptographic key (forgot oauth2:genkeys?)',
            0,
            $innerException
        );
    }
}
