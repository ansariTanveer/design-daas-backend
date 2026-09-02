<?php

namespace Application\Core\User\Enum;

enum AssociateDesktopGroupResult
{
    case OK;
    case INVALID_USER_GROUP;
    case INVALID_DESKTOP_GROUP;
    case DESKTOP_GROUP_ALREADY_IN_USER_GROUP;
}
