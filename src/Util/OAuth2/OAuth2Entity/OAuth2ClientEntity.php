<?php

namespace Application\Core\Util\OAuth2\OAuth2Entity;

use Assert\Assert;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class OAuth2ClientEntity implements ClientEntityInterface
{
    use EntityTrait;
    use ClientTrait;

    public function __construct()
    {
        $this->name = '';
        $this->redirectUri = [];
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @param array<string> $redirectUri
     */
    public function setRedirectUri(array $redirectUri): void
    {
        Assert::thatAll($redirectUri)->string();
        $this->redirectUri = $redirectUri;
    }

    public function setIsConfidential(bool $isConfidential): void
    {
        $this->isConfidential = $isConfidential;
    }
}
