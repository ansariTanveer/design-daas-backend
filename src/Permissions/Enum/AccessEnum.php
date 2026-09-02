<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Enum;

/**
 * @psalm-type AccessEnumValue = 'allow'|'deny'
 */
enum AccessEnum: string
{
    case ALLOW = 'allow';
    case DENY = 'deny';
}
