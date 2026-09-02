<?php

namespace Application\Core\User\Exception;

use LogicException;

class NoRawValueException extends LogicException implements PasswordExceptionInterface
{
}
