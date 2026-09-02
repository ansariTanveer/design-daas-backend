<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Model;

use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Repository\EndpointUserGroupAccessRepository;
use Application\Core\User\Model\UserGroup;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(repositoryClass: EndpointUserGroupAccessRepository::class)]
#[ORM\Table(name: 'endpoint_user_group_access')]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['endpoint_id', 'user_group_id'])]
class EndpointUserGroupAccess
{
    /** @psalm-var positive-int */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Endpoint::class, inversedBy: 'userGroupAccess')]
    #[ORM\JoinColumn(name: 'endpoint_id', referencedColumnName: 'id')]
    private Endpoint $endpoint;

    #[ORM\ManyToOne(targetEntity: UserGroup::class, inversedBy: 'endpointAccess')]
    #[ORM\JoinColumn(name: 'user_group_id', referencedColumnName: 'id')]
    private UserGroup $userGroup;

    #[ORM\Column(name: 'relation', type: 'string', length: 255, enumType: AccessEnum::class)]
    private AccessEnum $relation;

    public function __construct(
        Endpoint $endpoint,
        UserGroup $userGroup,
        AccessEnum $relation
    ) {
        $this->endpoint = $endpoint;
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

    public function endpoint(): Endpoint
    {
        return $this->endpoint;
    }

    public function userGroup(): UserGroup
    {
        return $this->userGroup;
    }

    public function relation(): AccessEnum
    {
        return $this->relation;
    }
}
