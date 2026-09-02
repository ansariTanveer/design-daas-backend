<?php

namespace Application\Core\Util\OAuth2\OAuth2Entity;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class OAuth2UserEntity implements UserEntityInterface
{
    use EntityTrait;
}
