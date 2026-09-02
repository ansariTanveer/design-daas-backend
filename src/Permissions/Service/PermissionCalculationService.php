<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Service;

use Application\Core\OAuth2\OAuth2AuthorizationValidator;
use Application\Core\Permissions\DTO\PermissionRequestDTO;
use Application\Core\Permissions\DTO\PermissionResultDTO;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Exception\PermissionsException;
use Application\Core\Permissions\Model\Endpoint;
use Application\Core\Permissions\Model\EndpointGroupUserAccess;
use Application\Core\Permissions\Model\EndpointGroupUserGroupAccess;
use Application\Core\Permissions\Model\EndpointUserAccess;
use Application\Core\Permissions\Model\EndpointUserGroupAccess;
use Application\Core\Permissions\Repository\EndpointGroupUserAccessRepository;
use Application\Core\Permissions\Repository\EndpointGroupUserGroupAccessRepository;
use Application\Core\Permissions\Repository\EndpointRepository;
use Application\Core\Permissions\Repository\EndpointUserAccessRepository;
use Application\Core\Permissions\Repository\EndpointUserGroupAccessRepository;
use Application\Core\User\Exception\BaseUserException;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Repository\UserRepository;
use Psr\Http\Message\ServerRequestInterface;

final readonly class PermissionCalculationService
{
    public function __construct(
        private UserRepository $userRepository,
        private EndpointRepository $endpointRepository,
        private EndpointUserAccessRepository $endpointUserAccessRepository,
        private EndpointUserGroupAccessRepository $endpointUserGroupAccessRepository,
        private EndpointGroupUserAccessRepository $endpointGroupUserAccessRepository,
        private EndpointGroupUserGroupAccessRepository $endpointGroupUserGroupAccessRepository,
        private OAuth2AuthorizationValidator $authorizationValidator,
    ) {
    }

    /**
     * @throws BaseUserException
     * @throws PermissionsException
     * @psalm-param non-empty-string $functionName
     * @psalm-param positive-int $userId
     */
    public function getPermissionAsDto(string $functionName, int $userId): PermissionResultDTO
    {
        $user = $this->userRepository->findBaseUserById($userId);
        if (!$user instanceof BaseUser) {
            throw BaseUserException::invalidId($userId);
        }

        $result = $this->getPermission($functionName, $userId);

        return PermissionResultDTO::fromUserAndAccessEnum($user, $result);
    }

    /**
     * @throws BaseUserException
     * @throws PermissionsException
     * @psalm-param non-empty-string $functionName
     * @psalm-param positive-int $userId
     */
    public function getPermission(string $functionName, int $userId): AccessEnum
    {
        $endpoint = $this->endpointRepository->findByFunctionName($functionName);
        if (!$endpoint instanceof Endpoint) {
            throw PermissionsException::invalidFunctionName();
        }

        $user = $this->userRepository->findBaseUserById($userId);
        if (!$user instanceof BaseUser) {
            throw BaseUserException::invalidId($userId);
        }

        $endpointUserAccess = $this->endpointUserAccessRepository->findByUserAndEndpoint(
            $user,
            $endpoint,
        );
        if ($endpointUserAccess instanceof EndpointUserAccess) {
            return $endpointUserAccess->relation();
        }

        foreach ($user->groups() as $userGroup) {
            $endpointUserGroupAccess = $this->endpointUserGroupAccessRepository->findByUserGroupAndEndpoint(
                $userGroup,
                $endpoint,
            );
            if ($endpointUserGroupAccess instanceof EndpointUserGroupAccess) {
                return $endpointUserGroupAccess->relation();
            }
        }

        $endpointGroupUserAccess = $this->endpointGroupUserAccessRepository->findByUserAndEndpointGroup(
            $user,
            $endpoint->endpointGroup(),
        );
        if ($endpointGroupUserAccess instanceof EndpointGroupUserAccess) {
            return $endpointGroupUserAccess->relation();
        }

        foreach ($user->groups() as $userGroup) {
            $endpointGroupUserGroupAccess =
                $this->endpointGroupUserGroupAccessRepository->findByUserGroupAndEndpointGroup(
                    $userGroup,
                    $endpoint->endpointGroup(),
                );
            if ($endpointGroupUserGroupAccess instanceof EndpointGroupUserGroupAccess) {
                return $endpointGroupUserGroupAccess->relation();
            }
        }

        return AccessEnum::DENY;
    }

    public function getPermissionsInfo(ServerRequestInterface $request, PermissionRequestDTO $dto): PermissionResultDTO
    {
        $resultDto = new PermissionResultDTO();
        $resultDto->result = AccessEnum::DENY->value;

        $userId = $this->authorizationValidator->tokenInfo($request, $dto->token);
        if (is_null($userId) || $userId < 1) {
            return $resultDto;
        }

        $user = $this->userRepository->findBaseUserById($userId);
        if (!$user instanceof BaseUser) {
            return $resultDto;
        }
        $result = $this->getPermission($dto->function_name, $userId);

        return PermissionResultDTO::fromUserAndAccessEnum($user, $result);
    }
}
