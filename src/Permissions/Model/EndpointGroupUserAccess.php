<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Model;

use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Repository\EndpointGroupUserAccessRepository;
use Application\Core\User\Model\BaseUser;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(repositoryClass: EndpointGroupUserAccessRepository::class)]
#[ORM\Table(name: 'endpoint_group_user_access')]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['endpoint_group_name', 'user_id'])]
class EndpointGroupUserAccess
{
    /** @psalm-var positive-int */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: EndpointGroup::class, inversedBy: 'userAccess')]
    #[ORM\JoinColumn(name: 'endpoint_group_name', referencedColumnName: 'unique_group_name')]
    private EndpointGroup $endpointGroup;

    #[ORM\ManyToOne(targetEntity: BaseUser::class, inversedBy: 'endpointGroupAccess')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private BaseUser $user;

    #[ORM\Column(name: 'relation', type: 'string', length: 255, enumType: AccessEnum::class)]
    private AccessEnum $relation;

    public function __construct(
        EndpointGroup $endpointGroup,
        BaseUser $user,
        AccessEnum $relation
    ) {
        $this->endpointGroup = $endpointGroup;
        $this->user = $user;
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

    public function user(): BaseUser
    {
        return $this->user;
    }

    public function relation(): AccessEnum
    {
        return $this->relation;
    }
}
