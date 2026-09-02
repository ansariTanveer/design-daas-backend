<?php

namespace Application\Core\Util\OAuth2\OAuth2Entity;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

class OAuth2ScopeEntity implements ScopeEntityInterface
{
    use EntityTrait;
    use ScopeTrait;
}
