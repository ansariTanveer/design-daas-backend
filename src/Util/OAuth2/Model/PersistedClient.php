<?php

namespace Application\Core\Util\OAuth2\Model;

use Application\Core\Util\OAuth2\Repository\ClientRepository;
use Assert\Assert;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'oauth2_clients')]
class PersistedClient
{
    #[ORM\Id]
    #[ORM\Column(name: 'identifier', type: 'string', length: 200, unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $identifier;

    #[ORM\Version]
    #[ORM\Column(name: 'version', type: 'integer')]
    private int $version;

    #[ORM\Column(name: 'name', type: 'string', length: 200, nullable: false)]
    private string $name;

    /** @var string[] */
    #[ORM\Column(name: 'redirect_uris', type: 'json', nullable: false)]
    private array $redirectUris;

    #[ORM\Column(name: 'is_confidential', type: 'boolean', nullable: false)]
    private bool $isConfidential;

    #[ORM\Column(name: 'secret', type: 'string', length: 200, nullable: true)]
    private ?string $secret;

    /**
     * @param array<string> $redirectUris
     */
    public function __construct(
        string $identifier,
        string $name,
        array $redirectUris,
        bool $isConfidential,
        ?string $secret
    ) {
        Assert::that($identifier)->betweenLength(1, 200);
        Assert::that($name)->betweenLength(1, 200);
        Assert::thatAll($redirectUris)->betweenLength(1, 200);
        Assert::that($secret)->nullOr()->betweenLength(1, 200);

        $this->identifier = $identifier;
        $this->name = $name;
        $this->redirectUris = array_values($redirectUris);
        $this->isConfidential = $isConfidential;
        $this->secret = $secret;
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return array<string>
     */
    public function redirectUris(): array
    {
        return $this->redirectUris;
    }

    public function isConfidential(): bool
    {
        return $this->isConfidential;
    }

    public function secret(): ?string
    {
        return $this->secret;
    }

    public function verifySecret(?string $secret): bool
    {
        /*
         * secret can be null or an empty string, both should be treated the same
         */
        return (string)$this->secret === (string)$secret;
    }

    public function version(): int
    {
        return $this->version;
    }
}
