<?php

declare(strict_types=1);

namespace Application\Core\Util\OAuth2\Exception;

use RuntimeException;
use Throwable;

final class OAuth2AccessControlMiddlewareException extends RuntimeException
{
    private function __construct(string $message = "", int $code = 0)
    {
        parent::__construct($message, $code);
    }

    public static function routeParsingFailed(): self
    {
        return new self('Failed to parse target action out of request', 0);
    }

    public static function unknownSchema(string $schema): self
    {
        return new self(
            'Schema "' . $schema . '" configured for action, but not known to OAuth2AccessControlMiddleware',
            1
        );
    }

    public static function authorizationValidatorLoadFailed(string $schema): self
    {
        return new self(
            'Failed to look up validator for Schema "' . $schema . '"',
            2
        );
    }

    public static function tooManyConfigurations(): self
    {
        return new self('Too many operation attributes on action', 3);
    }

    public static function actionMethodMismatch(string $requestMethod, string $actionMethod): self
    {
        return new self(
            'Action is configure for "' . $actionMethod . '", but called for a "' . $requestMethod . ' request',
            3
        );
    }
}
