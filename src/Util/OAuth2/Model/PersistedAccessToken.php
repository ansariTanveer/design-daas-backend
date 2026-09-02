<?php

namespace Application\Core\Util\OAuth2\Model;

use Application\Core\Util\OAuth2\OAuth2Entity\OAuth2ScopeEntity;
use Application\Core\Util\OAuth2\Repository\AccessTokenRepository;
use Assert\Assert;
use BjoernGoetschke\DateTime\Moment;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;

/** @final */
#[ORM\Entity(repositoryClass: AccessTokenRepository::class)]
#[ORM\Table(name: 'oauth2_access_token')]
class PersistedAccessToken
{
    #[ORM\Id]
    #[ORM\Column(name: 'identifier', type: 'string', length: 200, unique: true, nullable: false)]
    #[ORM\GeneratedValue(strategy: 'NONE')]
    private string $identifier;

    #[ORM\Column(name: 'create_moment', type: 'datetime_immutable', nullable: false)]
    private DateTimeImmutable $createMoment;

    #[ORM\Column(name: 'valid_until_moment', type: 'datetime_immutable', nullable: false)]
    private DateTimeImmutable $validUntilMoment;

    #[ORM\Column(name: 'client_identifier', type: 'string', length: 200, nullable: false)]
    private string $clientIdentifier;

    /** @var string[] */
    #[ORM\Column(name: 'scopes', type: 'json', nullable: false)]
    private array $scopes;

    #[ORM\Column(name: 'user_identifier', type: 'string', length: 200, nullable: true)]
    private ?string $userIdentifier;

    #[ORM\Column(name: 'revoke_moment', type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $revokeMoment = null;

    /**
     * @param array<string> $scopes
     */
    public function __construct(
        string $identifier,
        Moment $validUntilMoment,
        string $clientIdentifier,
        array $scopes,
        ?string $userIdentifier
    ) {
        Assert::that($identifier)->betweenLength(1, 200);
        Assert::that($clientIdentifier)->betweenLength(1, 200);
        Assert::thatAll($scopes)
            ->string()
            ->betweenLength(1, 200);
        Assert::that($userIdentifier)->nullOr()->betweenLength(1, 200);

        $this->identifier = $identifier;
        $this->validUntilMoment = $validUntilMoment->toImmutableDateTime();
        $this->clientIdentifier = $clientIdentifier;
        $this->scopes = $scopes;
        $this->userIdentifier = $userIdentifier;

        $this->createMoment = Moment::now()->toImmutableDateTime();
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function createMoment(): Moment
    {
        return Moment::fromDateTimeIgnoreTzNotNull($this->createMoment);
    }

    public function validUntilMoment(): Moment
    {
        return Moment::fromDateTimeIgnoreTzNotNull($this->validUntilMoment);
    }

    public function clientIdentifier(): string
    {
        return $this->clientIdentifier;
    }

    /**
     * @return ScopeEntityInterface[]
     */
    public function scopes(): array
    {
        return array_values(
            array_map(
                function (string $scope): ScopeEntityInterface {
                    $scopeEntity = new OAuth2ScopeEntity();
                    $scopeEntity->setIdentifier($scope);
                    return $scopeEntity;
                },
                $this->scopes
            )
        );
    }

    public function userIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function revokeMoment(): ?Moment
    {
        return Moment::fromDateTimeIgnoreTz($this->revokeMoment);
    }

    public function isRevoked(): bool
    {
        return $this->revokeMoment !== null;
    }

    public function revoke(): void
    {
        if (!$this->isRevoked()) {
            $this->revokeMoment = Moment::now()->toImmutableDateTime();
        }
    }

    public static function fromAccessToken(AccessTokenEntityInterface $token): self
    {
        $validUntilMoment = Moment::fromDateTimeNotNull($token->getExpiryDateTime())->withUtcTimezone();

        $scopes = array_values(
            array_map(
                function (ScopeEntityInterface $scopeEntity): string {
                    return $scopeEntity->getIdentifier();
                },
                $token->getScopes()
            )
        );

        return new self(
            $token->getIdentifier(),
            $validUntilMoment,
            $token->getClient()->getIdentifier(),
            $scopes,
            !is_null($token->getUserIdentifier()) ? (string)$token->getUserIdentifier() : null
        );
    }
}
