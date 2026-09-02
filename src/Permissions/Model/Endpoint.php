<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Model;

use Application\Core\Permissions\Repository\EndpointRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @final
 * @psalm-type httpMethod = 'get'|'head'|'post'|'put'|'delete'|'connect'|'options'|'trace'
 */
#[ORM\Entity(repositoryClass: EndpointRepository::class)]
#[ORM\Table(name: 'endpoint')]
class Endpoint
{
    /** @psalm-var int<1, 2147483647> */
    #[ORM\Id]
    #[ORM\Column(name: 'id', type: 'integer')]
    private int $id;

    /** @psalm-var non-empty-string */
    #[ORM\Column(name: 'function_name', type: 'string', length: 255)]
    private string $functionName;

    /** @psalm-var non-empty-string */
    #[ORM\Column(name: 'endpoint_url', type: 'string', length: 255)]
    private string $endpointUrl;

    /** @psalm-var httpMethod */
    #[ORM\Column('method', type: 'string', length: 16)]
    private string $method;

    #[ORM\ManyToOne(targetEntity: EndpointGroup::class, cascade: ['refresh'], inversedBy: 'endpoints')]
    #[ORM\JoinColumn(name: 'endpoint_group_name', referencedColumnName: 'unique_group_name')]
    private EndpointGroup $endpointGroup;

    /** @psalm-var Collection<int, EndpointUserAccess> */
    #[ORM\OneToMany(
        mappedBy: 'endpoint',
        targetEntity: EndpointUserAccess::class,
        cascade: ['refresh', 'remove']
    )]
    private Collection $userAccess;

    /** @psalm-var Collection<int, EndpointUserGroupAccess> */
    #[ORM\OneToMany(
        mappedBy: 'endpoint',
        targetEntity: EndpointUserGroupAccess::class,
        cascade: ['refresh', 'remove']
    )]
    private Collection $userGroupAccess;

    /**
     * @psalm-param int<1, 2147483647> $id
     * @psalm-param non-empty-string $functionName
     * @psalm-param non-empty-string $endpointUrl
     * @psalm-param httpMethod $method
     */
    public function __construct(
        int $id,
        string $functionName,
        string $endpointUrl,
        string $method,
        EndpointGroup $endpointGroup
    ) {
        assert($id > 0 && $id <= 2147483647);
        assert(strlen($functionName) > 0);
        assert(strlen($endpointUrl) > 0);
        assert(strlen($method) > 0);

        $this->id = $id;
        $this->functionName = $functionName;
        $this->endpointUrl = $endpointUrl;
        $this->endpointGroup = $endpointGroup;
        $this->method = $method;

        $this->userAccess = new ArrayCollection();
        $this->userGroupAccess = new ArrayCollection();
    }

    /**
     * @psalm-return int<1, 2147483647>
     */
    public function id(): int
    {
        return $this->id;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function functionName(): string
    {
        return $this->functionName;
    }

    /**
     * @psalm-return non-empty-string
     */
    public function endpointUrl(): string
    {
        return $this->endpointUrl;
    }

    /**
     * @return httpMethod
     */
    public function method(): string
    {
        return $this->method;
    }

    public function endpointGroup(): EndpointGroup
    {
        return $this->endpointGroup;
    }

    /**
     * @return array<EndpointUserAccess>
     */
    public function userAccess(): array
    {
        return $this->userAccess->toArray();
    }

    /**
     * @return array<EndpointUserGroupAccess>
     */
    public function userGroupAccess(): array
    {
        return $this->userGroupAccess->toArray();
    }
}
