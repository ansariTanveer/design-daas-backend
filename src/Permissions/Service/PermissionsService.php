<?php

declare(strict_types=1);

namespace Application\Core\Permissions\Service;

use Application\Core\Permissions\DTO\InfrastructurePermissionsDTO;
use Application\Core\Permissions\Exception\PermissionsException;
use Application\Core\Permissions\Repository\EndpointGroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use stdClass;

final readonly class PermissionsService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EndpointGroupRepository $endpointGroupRepository,
    ) {
    }

    /**
     * @param resource $stream
     * @throws PermissionsException
     */
    public function updatePermissionsFromStream($stream): void
    {
        $permissionsDTO = $this->infrastructurePermissionsDTOFromStream($stream);
        $this->updatePermissions($permissionsDTO);
    }

    /**
     * @param resource $stream
     * @throws PermissionsException
     */
    private function infrastructurePermissionsDTOFromStream($stream): InfrastructurePermissionsDTO
    {
        $streamData = stream_get_contents($stream);
        if ($streamData === false) {
            throw PermissionsException::errorReadingStream();
        }

        $json = json_decode($streamData);
        if (!$json instanceof stdClass) {
            throw PermissionsException::errorParsingJson();
        }

        return InfrastructurePermissionsDTO::fromJson($json);
    }

    private function updatePermissions(InfrastructurePermissionsDTO $dto): void
    {
        $this->em->wrapInTransaction(function () use ($dto) {
            $endpointGroup = $this->endpointGroupRepository->find($dto->unique_group_name);
            if (!is_null($endpointGroup)) {
                $this->em->remove($endpointGroup);
            }

            $endpointGroup = $dto->toEntity();
            foreach ($dto->endpoints as $endpointDTO) {
                $endpoint = $endpointDTO->toEntity($endpointGroup);
                $this->em->persist($endpoint);
            }

            $this->em->persist($endpointGroup);
        });

        $this->em->flush();
    }
}
