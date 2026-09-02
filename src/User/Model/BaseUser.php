<?php

declare(strict_types=1);

namespace Application\Core\User\Model;

use Application\Core\Permissions\Model\EndpointGroupUserAccess;
use Application\Core\Permissions\Model\EndpointUserAccess;
use Application\Core\User\Enum\UserRole;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Repository\UserRepository;
use BjoernGoetschke\DateTime\Interval;
use BjoernGoetschke\DateTime\Moment;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: "users")]
#[ORM\InheritanceType("SINGLE_TABLE")]
#[ORM\Index(columns: ["guid"], name: "guid_idx")]
#[ORM\Index(columns: ["email","registration_code"], name: "registration_idx")]
#[ORM\DiscriminatorColumn(name: "role", type: "string", length: 16)]
#[ORM\DiscriminatorMap([
    "admin" => Admin::class,
    "user" => User::class
])]
abstract class BaseUser
{
    public const REGISTRATION_CODE_LENGTH = 8; // Use even ints
    public const REGISTRATION_CODE_TIMEOUT_HOURS = 24;

    #[ORM\Id]
    #[ORM\Column(name: "id", type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id; /** @phpstan-ignore-line */

    #[ORM\Column(name: "guid", type: "string", length: 255, nullable: false)]
    private string $guid;

    #[ORM\Column(name: "name", type: "string", length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(name: "email", type: "string", length: 255, nullable: false)]
    private string $email;

    #[ORM\Column(name: "password", type: "string", length: 255, nullable: false)]
    private string $password;

    #[ORM\Column(name: "enabled", type: "boolean", nullable: false)]
    private bool $enabled = false;

    /** @psalm-var non-empty-string|null */
    #[ORM\Column(name: "registration_code", type: "string", length: 8, nullable: true)]
    private ?string $registrationCode;

    #[ORM\Column(name: "registration_code_timeout", type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $registrationCodeTimeout;

    /**
     * The following variable is only used during creation.
     * To distinguish roles use `($user instanceof Admin)` or `($user instanceof User)`
     */
    private string $role; /** @phpstan-ignore-line */

    /** @psalm-var Collection<int, UserGroup> */
    #[ORM\ManyToMany(targetEntity: UserGroup::class, inversedBy: 'users', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    #[ORM\JoinTable(
        name: 'users_to_user_groups',
        joinColumns: [
            new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'user_group_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $groups;

    /** @psalm-var Collection<int, EndpointUserAccess> */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: EndpointUserAccess::class,
        cascade: ['persist', 'refresh', 'remove']
    )]
    private Collection $endpointAccess;

    /** @psalm-var Collection<int, EndpointGroupUserAccess> */
    #[ORM\OneToMany(
        mappedBy: 'user',
        targetEntity: EndpointGroupUserAccess::class,
        cascade: ['persist', 'refresh', 'remove']
    )]
    private Collection $endpointGroupAccess;

    #[ORM\Column(name: "registration_used_moment", type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $registrationUsedMoment = null;

    private static function generateGUID(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0b0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 0b10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    protected function __construct(
        string $name,
        string $email,
        Password $password,
        UserRole $role
    ) {
        $this->name = $name;
        $this->email = $email;
        $this->password = $password->asHash();
        $this->role = $role->value;
        $this->guid = self::generateGUID();

        $this->groups = new ArrayCollection();
        $this->endpointAccess = new ArrayCollection();
        $this->endpointGroupAccess = new ArrayCollection();
    }

    /**
     * @psalm-return positive-int
     */
    public function id(): int
    {
        assert($this->id !== null && $this->id > 0);
        return $this->id;
    }

    public function guid(): string
    {
        return $this->guid;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function password(): Password
    {
        return Password::fromHash($this->password);
    }

    public function setPassword(Password $password): void
    {
        $this->password = $password->asHash();
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return UserGroup[]
     */
    public function groups(): array
    {
        return $this->groups->toArray();
    }

    /**
     * @throws BaseUserException
     */
    public function addGroup(UserGroup $group): void
    {
        if ($this->groups->contains($group)) {
            throw BaseUserException::userAlreadyBelongsToGroup();
        }

        $this->groups->add($group);
    }

    /**
     * @throws BaseUserException
     */
    public function removeGroup(UserGroup $group): void
    {
        if (!$this->groups->contains($group)) {
            throw BaseUserException::userGroupNotFound();
        }

        $this->groups->removeElement($group);
    }

    public function hasGroup(UserGroup $group): bool
    {
        foreach ($this->groups as $userGroup) {
            if ($userGroup->id() === $group->id()) {
                return true;
            }
        }

        return false;
    }

    public function disable(): void
    {
        $this->enabled = false;
    }

    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * @psalm-return non-empty-string|null
     */
    public function registrationCode(): ?string
    {
        return $this->registrationCode;
    }

    public function registrationCodeExpired(): bool
    {
        $timeout = Moment::fromDateTimeIgnoreTz($this->registrationCodeTimeout);

        return is_null($timeout) || $timeout->isInThePast();
    }

    protected function setNewRegistrationCode(Moment $timeout = null): void
    {
        if (is_null($timeout)) {
            $timeout = Moment::now()->add(Interval::hours(self::REGISTRATION_CODE_TIMEOUT_HOURS));
        }

        $this->registrationCode = $this->generateRegistrationCode();
        $this->registrationCodeTimeout = $timeout->toImmutableDateTime();
    }

    /** @return non-empty-string */
    private function generateRegistrationCode(): string
    {
        return strtoupper(bin2hex(random_bytes(self::REGISTRATION_CODE_LENGTH / 2))); // 1 byte = 2 hex characters
    }

    /**
     * @return array<EndpointUserAccess>
     */
    public function endpointAccess(): array
    {
        return $this->endpointAccess->toArray();
    }

    /**
     * @return array<EndpointGroupUserAccess>
     */
    public function endpointGroupAccess(): array
    {
        return $this->endpointGroupAccess->toArray();
    }

    public function registrationUsedMoment(): ?Moment
    {
        return Moment::fromDateTimeIgnoreTz($this->registrationUsedMoment);
    }

    public function setRegistrationUsedMoment(Moment $newRegistrationUsedMoment): void
    {
        $this->registrationUsedMoment = $newRegistrationUsedMoment->toImmutableDateTime();
    }
}
