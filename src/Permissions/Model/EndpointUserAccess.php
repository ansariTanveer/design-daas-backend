<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Model;

use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Repository\EndpointUserAccessRepository;
use Application\Core\User\Model\BaseUser;
use Doctrine\ORM\Mapping as ORM;

/** @final */
#[ORM\Entity(repositoryClass: EndpointUserAccessRepository::class)]
#[ORM\Table(name: 'endpoint_user_access')]
#[ORM\UniqueConstraint(name: 'lookup_unique_idx', columns: ['endpoint_id', 'user_id'])]
class EndpointUserAccess
{
    /** @psalm-var positive-int */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\GeneratedValue]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Endpoint::class, inversedBy: 'userAccess')]
    #[ORM\JoinColumn(name: 'endpoint_id', referencedColumnName: 'id')]
    private Endpoint $endpoint;

    #[ORM\ManyToOne(targetEntity: BaseUser::class, inversedBy: 'endpointAccess')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')]
    private BaseUser $user;

    #[ORM\Column(name: 'relation', type: 'string', length: 255, enumType: AccessEnum::class)]
    private AccessEnum $relation;

    public function __construct(
        Endpoint $endpoint,
        BaseUser $user,
        AccessEnum $relation
    ) {
        $this->endpoint = $endpoint;
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

    public function endpoint(): Endpoint
    {
        return $this->endpoint;
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
