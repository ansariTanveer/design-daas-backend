<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Model;

use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Repository\EndpointGroupUserGroupAccessRepository;
use Application\Core\User\Model\UserGroup;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(repositoryClass: EndpointGroupUserGroupAccessRepository::class)]
#[ORM\Table(name: 'endpoint_group_user_group_access')]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['endpoint_group_name', 'user_group_id'])]
class EndpointGroupUserGroupAccess
{
    /** @psalm-var positive-int */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: EndpointGroup::class, inversedBy: 'userGroupAccess')]
    #[ORM\JoinColumn(name: 'endpoint_group_name', referencedColumnName: 'unique_group_name')]
    private EndpointGroup $endpointGroup;

    #[ORM\ManyToOne(targetEntity: UserGroup::class, inversedBy: 'endpointGroupAccess')]
    #[ORM\JoinColumn(name: 'user_group_id', referencedColumnName: 'id')]
    private UserGroup $userGroup;

    #[ORM\Column(name: 'relation', type: 'string', length: 255, enumType: AccessEnum::class)]
    private AccessEnum $relation;

    public function __construct(
        EndpointGroup $endpointGroup,
        UserGroup $userGroup,
        AccessEnum $relation
    ) {
        $this->endpointGroup = $endpointGroup;
        $this->userGroup = $userGroup;
        $this->relation = $relation;
    }

    /**
     * @psalm-return positive-int
     */
    public function id(): int
    {
        return $this->id;
    }

    public function endpointGroup(): EndpointGroup
    {
        return $this->endpointGroup;
    }

    public function userGroup(): UserGroup
    {
        return $this->userGroup;
    }

    public function relation(): AccessEnum
    {
        return $this->relation;
    }

    public function setRelation(AccessEnum $newRelation): void
    {
        $this->relation = $newRelation;
    }
}
