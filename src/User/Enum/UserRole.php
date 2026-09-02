<?php

namespace Application\Core\User\Enum;

enum UserRole: string
{
    case ADMIN = "admin";
    case USER = "user";
}
