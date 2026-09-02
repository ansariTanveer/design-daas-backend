<?php

declare(strict_types=1);

namespace Application\Core\Desktop\Model;

use Application\Core\Desktop\Exception\DesktopGroupException;
use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\Desktop\Repository\DesktopGroupRepository;
use Application\Core\User\Model\UserGroup;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

/** @final */
#[ORM\Entity(repositoryClass: DesktopGroupRepository::class)]
#[ORM\Table(name: 'desktop_groups')]
class DesktopGroup
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    private string $description;

    /** @psalm-var Collection<int, Desktop> */
    #[ORM\ManyToMany(
        targetEntity: Desktop::class,
        mappedBy: 'groups',
        fetch: 'EXTRA_LAZY'
    )]
    private Collection $desktops;

    /** @psalm-var Collection<int, UserGroup> */
    #[ORM\ManyToMany(
        targetEntity: UserGroup::class,
        inversedBy: 'desktopGroups',
        cascade: ['persist'],
        fetch: 'EXTRA_LAZY'
    )]
    private Collection $userGroups;

    public function __construct(
        string $description
    ) {
        Assert::notEmpty($description);

        $this->description = $description;
        $this->desktops = new ArrayCollection();
        $this->userGroups = new ArrayCollection();
    }

    public function id(): int
    {
        assert(is_int($this->id));

        return $this->id;
    }

    public function description(): string
    {
        return $this->description;
    }

    /**
     * @return array<Desktop>
     */
    public function desktops(): array
    {
        return $this->desktops->toArray();
    }

    /**
     * @psalm-return array<positive-int>
     */
    public function desktopIds(): array
    {
        $ids = [];
        foreach ($this->desktops as $desktop) {
            $id = $desktop->id();
            assert($id > 0);
            $ids[] = $id;
        }

        return $ids;
    }

    /**
     * @throws DesktopException
     */
    public function addDesktop(Desktop $desktop): void
    {
        $desktop->addGroup($this);
    }

    /**
     * @throws DesktopException
     */
    public function removeDesktop(Desktop $desktop): void
    {
        $desktop->removeGroup($this);
    }

    /**
     * @return array<UserGroup>
     */
    public function userGroups(): array
    {
        return $this->userGroups->toArray();
    }

    /**
     * @throws DesktopGroupException - User Group is already associated with Desktop Group
     */
    public function addUserGroup(UserGroup $userGroup): void
    {
        if ($this->userGroups->contains($userGroup)) {
            throw DesktopGroupException::userGroupAlreadyInDesktopGroup();
        }

        $this->userGroups->add($userGroup);
    }

    /**
     * @throws DesktopGroupException - User Group is not associated with Desktop Group
     */
    public function removeUserGroup(UserGroup $userGroup): void
    {
        if (!$this->userGroups->contains($userGroup)) {
            throw DesktopGroupException::userGroupNotInDesktopGroup();
        }

        $this->userGroups->removeElement($userGroup);
    }
}
