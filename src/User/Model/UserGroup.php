<?php

declare(strict_types=1);

namespace Application\Core\User\Model;

use Application\Core\Desktop\Exception\DesktopGroupException;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\Permissions\Model\EndpointGroupUserGroupAccess;
use Application\Core\Permissions\Model\EndpointUserGroupAccess;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Repository\UserGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(repositoryClass: UserGroupRepository::class)]
#[ORM\Table(name: 'user_groups')]
class UserGroup
{
    /** @psalm-var positive-int */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    private string $description;

    /** @psalm-var Collection<int, BaseUser> */
    #[ORM\ManyToMany(targetEntity: BaseUser::class, mappedBy: 'groups', cascade: ['persist'], fetch: 'EXTRA_LAZY')]
    private Collection $users;

    /** @psalm-var Collection<int, DesktopGroup> */
    #[ORM\ManyToMany(
        targetEntity: DesktopGroup::class,
        mappedBy: 'userGroups',
        cascade: ['persist'],
        fetch: 'EXTRA_LAZY'
    )]
    private Collection $desktopGroups;

    /** @psalm-var Collection<int, EndpointUserGroupAccess> */
    #[ORM\OneToMany(
        mappedBy: 'userGroup',
        targetEntity: EndpointUserGroupAccess::class,
        cascade: ['persist', 'refresh', 'remove']
    )]
    private Collection $endpointAccess;

    /** @psalm-var Collection<int, EndpointGroupUserGroupAccess> */
    #[ORM\OneToMany(
        mappedBy: 'userGroup',
        targetEntity: EndpointGroupUserGroupAccess::class,
        cascade: ['persist', 'refresh', 'remove']
    )]
    private Collection $endpointGroupAccess;

    public function __construct(
        string $description
    ) {
        $this->description = $description;

        $this->users = new ArrayCollection();
        $this->desktopGroups = new ArrayCollection();
        $this->endpointAccess = new ArrayCollection();
        $this->endpointGroupAccess = new ArrayCollection();
    }

    /**
     * @psalm-return positive-int
     */
    public function id(): int
    {
        assert(is_int($this->id));

        return $this->id;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<User>
     */
    public function users(): array
    {
        /** @var array<User> */
        return $this->users->toArray();
    }

    /**
     * @throws BaseUserException
     */
    public function addUser(User $user): void
    {
        $user->addGroup($this);
    }

    /**
     * @throws BaseUserException
     */
    public function removeUser(User $user): void
    {
        $user->removeGroup($this);
    }

    public function hasUser(User $user): bool
    {
        foreach ($this->users as $userInGroup) {
            if ($userInGroup->id() === $user->id()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<DesktopGroup>
     */
    public function desktopGroups(): array
    {
        return $this->desktopGroups->toArray();
    }

    /**
     * @throws DesktopGroupException
     */
    public function addDesktopGroup(DesktopGroup $desktopGroup): void
    {
        $desktopGroup->addUserGroup($this);
    }

    /**
     * @throws DesktopGroupException
     */
    public function removeDesktopGroup(DesktopGroup $desktopGroup): void
    {
        $desktopGroup->removeUserGroup($this);
    }

    public function hasDesktopGroup(DesktopGroup $desktopGroup): bool
    {
        return $this->desktopGroups->contains($desktopGroup);
    }

    /**
     * @return array<EndpointUserGroupAccess>
     */
    public function endpointAccess(): array
    {
        return $this->endpointAccess->toArray();
    }

    /**
     * @return array<EndpointGroupUserGroupAccess>
     */
    public function endpointGroupAccess(): array
    {
        return $this->endpointGroupAccess->toArray();
    }
}
