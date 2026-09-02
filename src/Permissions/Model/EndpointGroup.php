<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Model;

use Application\Core\Permissions\Repository\EndpointGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(repositoryClass: EndpointGroupRepository::class)]
#[ORM\Table(name: 'endpoint_group')]
class EndpointGroup
{
    /** @psalm-var non-empty-string */
    #[ORM\Id]
    #[ORM\Column(name: 'unique_group_name', type: 'string', unique: true)]
    private string $uniqueGroupName;

    /** @psalm-var Collection<int, Endpoint> */
    #[ORM\OneToMany(
        mappedBy: 'endpointGroup',
        targetEntity: Endpoint::class,
        cascade: ['refresh', 'remove']
    )]
    private Collection $endpoints;

    /** @psalm-var Collection<int, EndpointGroupUserAccess> */
    #[ORM\OneToMany(
        mappedBy: 'endpointGroup',
        targetEntity: EndpointGroupUserAccess::class,
        cascade: ['refresh', 'remove']
    )]
    private Collection $userAccess;

    /** @psalm-var Collection<int, EndpointGroupUserGroupAccess> */
    #[ORM\OneToMany(
        mappedBy: 'endpointGroup',
        targetEntity: EndpointGroupUserGroupAccess::class,
        cascade: ['refresh', 'remove']
    )]
    private Collection $userGroupAccess;


    /**
     * @psalm-param  non-empty-string $uniqueGroupName
     */
    public function __construct(
        string $uniqueGroupName
    ) {
        assert(strlen($uniqueGroupName) > 0);

        $this->uniqueGroupName = $uniqueGroupName;
        $this->endpoints = new ArrayCollection();

        $this->userAccess = new ArrayCollection();
        $this->userGroupAccess = new ArrayCollection();
    }

    /**
     * @return non-empty-string
     */
    public function uniqueGroupName(): string
    {
        return $this->uniqueGroupName;
    }

    /**
     * @return array<Endpoint>
     */
    public function endpoints(): array
    {
        return $this->endpoints->toArray();
    }

    /**
     * @return array<EndpointGroupUserAccess>
     */
    public function userAccess(): array
    {
        return $this->userAccess->toArray();
    }

    /**
     * @return array<EndpointGroupUserGroupAccess>
     */
    public function userGroupAccess(): array
    {
        return $this->userGroupAccess->toArray();
    }

    public function addUserGroupAccess(EndpointGroupUserGroupAccess $endpointGroupUserGroupAccess): void
    {
        $this->userGroupAccess->add($endpointGroupUserGroupAccess);
    }

    public function removeUserGroupAccess(EndpointGroupUserGroupAccess $endpointGroupUserGroupAccess): void
    {
        $this->userGroupAccess->removeElement($endpointGroupUserGroupAccess);
    }

    public function hasUserGroupAccess(EndpointGroupUserGroupAccess $endpointGroupUserGroupAccess): bool
    {
        return $this->userGroupAccess->contains($endpointGroupUserGroupAccess);
    }
}
