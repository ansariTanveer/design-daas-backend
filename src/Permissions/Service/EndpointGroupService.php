<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Service;

use Application\Core\Permissions\Exception\EndpointGroupException;
use Application\Core\Permissions\Exception\EndpointGroupUserGroupAccessException;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Model\EndpointGroup;
use Application\Core\Permissions\Model\EndpointGroupUserGroupAccess;
use Application\Core\Permissions\Repository\EndpointGroupRepository;
use Application\Core\Permissions\Repository\EndpointGroupUserGroupAccessRepository;
use Application\Core\User\Exception\UserGroupException;
use Application\Core\User\Model\UserGroup;
use Application\Core\User\Repository\UserGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityNotFoundException;

final readonly class EndpointGroupService
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserGroupRepository $userGroupRepository,
        private EndpointGroupRepository $endpointGroupRepository,
        private EndpointGroupUserGroupAccessRepository $accessRepository,
    ) {
    }

    /**
     * @psalm-param 'allow'|'deny' $access
     * @throws EntityNotFoundException
     */
    public function associateUserGroup(string $endpointGroupName, int $userGroupId, string $access): void
    {
        $userGroup = $this->userGroupRepository->find($userGroupId);
        if (!($userGroup instanceof UserGroup)) {
            throw new EntityNotFoundException('User group not found');
        }

        $endpointGroup = $this->endpointGroupRepository->findByUniqueEndpointGroupName($endpointGroupName);
        if (!($endpointGroup instanceof EndpointGroup)) {
            throw new EntityNotFoundException('Endpoint group not found');
        }

        $accessType = ($access === 'allow') ? AccessEnum::ALLOW : AccessEnum::DENY;

        $endpointGroupUserGroupAccess = new EndpointGroupUserGroupAccess($endpointGroup, $userGroup, $accessType);

        $foundAccess = $this->accessRepository->findByUserGroupAndEndpointGroup($userGroup, $endpointGroup);
        if (!($foundAccess instanceof EndpointGroupUserGroupAccess)) {
            //Does not exist, just create it
            $endpointGroup->addUserGroupAccess($endpointGroupUserGroupAccess);
            $this->em->persist($endpointGroupUserGroupAccess);
        } else {
            if ($foundAccess->relation() !== $endpointGroupUserGroupAccess->relation()) {
                $foundAccess->setRelation($endpointGroupUserGroupAccess->relation());
                $this->em->persist($foundAccess);
            }
        }

        $this->em->flush();
    }


    /**
     * @throws EndpointGroupException
     * @throws UserGroupException
     * @throws EndpointGroupUserGroupAccessException
     */
    public function deleteEndpointGroupUserGroupAccess(string $endpointGroupId, int $userGroupId): void
    {
        $endpointGroup = $this->endpointGroupRepository->find($endpointGroupId);
        if (!($endpointGroup instanceof EndpointGroup)) {
            throw EndpointGroupException::endpointGroupNotFound();
        }

        $userGroup = $this->userGroupRepository->find($userGroupId);
        if (!($userGroup instanceof UserGroup)) {
            throw UserGroupException::userGroupNotFound();
        }

        $endpointGroupUserGroupAccess = $this->accessRepository
            ->findByUserGroupAndEndpointGroup(
                $userGroup,
                $endpointGroup
            );
        if (!($endpointGroupUserGroupAccess instanceof EndpointGroupUserGroupAccess)) {
            throw EndpointGroupUserGroupAccessException::invalidEndpointGroupUserGroupAccess();
        }

        $this->em->remove($endpointGroupUserGroupAccess);
    }
}
