<?php

declare(strict_types=1);

namespace Application\Core\Desktop\Model;

use Application\Core\Desktop\Exception\DesktopException;
use Application\Core\Desktop\Repository\DesktopRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Webmozart\Assert\Assert;

/** @final */
#[ORM\Entity(repositoryClass: DesktopRepository::class)]
#[ORM\Table(name: 'desktops')]
class Desktop
{
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private ?int $id;

    #[ORM\Column(name: 'description', type: 'string', length: 255)]
    private string $description;

    /** @psalm-var Collection<int, DesktopGroup> */
    #[ORM\ManyToMany(
        targetEntity: DesktopGroup::class,
        inversedBy: 'desktops',
        fetch: 'EXTRA_LAZY'
    )]
    #[ORM\JoinTable(
        name: 'desktops_to_desktop_groups',
        joinColumns: [
            new ORM\JoinColumn(name: 'desktop_id', referencedColumnName: 'id')
        ],
        inverseJoinColumns: [
            new ORM\JoinColumn(name: 'desktop_group_id', referencedColumnName: 'id')
        ]
    )]
    private Collection $groups;

    public function __construct(
        string $description
    ) {
        Assert::notEmpty($description);

        $this->description = $description;
        $this->groups = new ArrayCollection();
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
     * @return DesktopGroup[]
     */
    public function groups(): array
    {
        return $this->groups->toArray();
    }

    /**
     * @throws DesktopException
     */
    public function addGroup(DesktopGroup $group): void
    {
        if ($this->groups->contains($group)) {
            throw DesktopException::alreadyInGroup();
        }
        $this->groups->add($group);
    }

    /**
     * @throws DesktopException
     */
    public function removeGroup(DesktopGroup $group): void
    {
        if (!$this->groups->contains($group)) {
            throw DesktopException::notInGroup();
        }
        $this->groups->removeElement($group);
    }
}
