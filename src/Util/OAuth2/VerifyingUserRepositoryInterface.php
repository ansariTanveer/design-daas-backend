<?php

namespace Application\Core\Util\OAuth2;

use League\OAuth2\Server\Repositories\UserRepositoryInterface;

interface VerifyingUserRepositoryInterface extends UserRepositoryInterface
{
    public function verifyUserIdentifier(string $identifier): bool;
}
