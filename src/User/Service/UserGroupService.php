<?php

declare(strict_types=1);

namespace Application\Core\User\Service;

use Application\Core\Desktop\DTO\DesktopGroupDetailsDTO;
use Application\Core\Desktop\Exception\DesktopGroupException;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\Desktop\Repository\DesktopGroupRepository;
use Application\Core\User\DTO\UserGroupUpdateDTO;
use Application\Core\User\Enum\AssociateDesktopGroupResult;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Exception\UserGroupServiceException;
use Application\Core\User\Model\UserGroup;
use Application\Core\User\Repository\UserGroupRepository;
use Application\Core\User\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;

final readonly class UserGroupService
{
    public function __construct(
        private UserGroupRepository $userGroupRepository,
        private DesktopGroupRepository $desktopGroupRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em
    ) {
    }

    /**
     * @throws UserGroupServiceException
     */
    public function createGroup(string $description): UserGroup
    {
        if (strlen(trim($description)) === 0) {
            throw UserGroupServiceException::descriptionCannotBeEmpty();
        }

        $group = new UserGroup($description);
        $this->userGroupRepository->store($group);
        return $group;
    }

    /** @return UserGroup[] */
    public function getAllGroups(): array
    {
        /** @var UserGroup[] $groups */
        $groups = $this->userGroupRepository->findAll();
        return $groups;
    }

    /**
     * @throws BaseUserException
     * @throws UserGroupServiceException
     */
    public function updateGroup(int $groupId, UserGroupUpdateDTO $requestDto): UserGroup
    {
        $group = $this->userGroupRepository->find($groupId);
        if (!($group instanceof UserGroup)) {
            throw UserGroupServiceException::userGroupNotFound();
        }

        if ($requestDto->hasProperty('userIds')) {
            $usersNowInGroup = $this->userRepository->findBaseUsers($requestDto->userIds);

            foreach ($group->users() as $userCurrentlyInGroup) {
                if (!in_array($userCurrentlyInGroup->id(), $requestDto->userIds, true)) {
                    $group->removeUser($userCurrentlyInGroup);
                }
            }

            foreach ($usersNowInGroup as $userNowInGroup) {
                if (!$group->hasUser($userNowInGroup)) {
                    $group->addUser($userNowInGroup);
                }
            }
        }

        if ($requestDto->hasProperty('description')) {
            if (strlen(trim($requestDto->description)) === 0) {
                throw UserGroupServiceException::descriptionCannotBeEmpty();
            }
            $group->setDescription($requestDto->description);
        }

        $this->em->flush();

        return $group;
    }

    /**
     * @throws UserGroupServiceException - User Group not found
     */
    public function getGroup(int $groupId): UserGroup
    {
        $group = $this->userGroupRepository->find($groupId);
        if (!($group instanceof UserGroup)) {
            throw UserGroupServiceException::userGroupNotFound();
        }

        return $group;
    }

    /**
     * @return object{
     *     result: AssociateDesktopGroupResult,
     *     updatedList: null|array<DesktopGroupDetailsDTO>
     * }
     * @throws DesktopGroupException
     */
    public function associateDesktopGroup(int $userGroupId, int $desktopGroupId): object
    {
        $userGroup = $this->userGroupRepository->find($userGroupId);
        if (!($userGroup instanceof UserGroup)) {
            return (object)[
                'result' => AssociateDesktopGroupResult::INVALID_USER_GROUP,
                'updatedList' => null,
            ];
        }

        $desktopGroup = $this->desktopGroupRepository->find($desktopGroupId);
        if (!($desktopGroup instanceof DesktopGroup)) {
            return (object)[
                'result' => AssociateDesktopGroupResult::INVALID_DESKTOP_GROUP,
                'updatedList' => null,
            ];
        }

        if ($userGroup->hasDesktopGroup($desktopGroup)) {
            return (object)[
                'result' => AssociateDesktopGroupResult::DESKTOP_GROUP_ALREADY_IN_USER_GROUP,
                'updatedList' => null,
            ];
        }

        $userGroup->addDesktopGroup($desktopGroup);
        $this->em->flush();

        return (object)[
            'result' => AssociateDesktopGroupResult::OK,
            'updatedList' => DesktopGroupDetailsDTO::fromEntities($userGroup->desktopGroups())
        ];
    }
}
