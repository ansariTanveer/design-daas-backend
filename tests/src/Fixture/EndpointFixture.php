<?php

namespace Application\Test\Fixture;

use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Model\Endpoint;
use Application\Core\Permissions\Model\EndpointGroup;
use Application\Core\Permissions\Model\EndpointGroupUserAccess;
use Application\Core\Permissions\Model\EndpointGroupUserGroupAccess;
use Application\Core\Permissions\Model\EndpointUserAccess;
use Application\Core\Permissions\Model\EndpointUserGroupAccess;
use Application\Core\User\Model\User;
use Application\Core\User\Model\UserGroup;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @psalm-type EndpointFixtureOutput = object{
 *     user: User,
 *     userGroup: UserGroup,
 *     endpoint: Endpoint,
 *     endpointGroup: EndpointGroup,
 *     endpointUserAccess: EndpointUserAccess|null,
 *     endpointUserGroupAccess: EndpointUserGroupAccess|null,
 *     endpointGroupUserAccess: EndpointGroupUserAccess|null,
 *     endpointGroupUserGroupAccess: EndpointGroupUserGroupAccess|null
 * }
 */
final readonly class EndpointFixture
{
    private EntityManagerInterface $em;

    public function __construct(
        EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    /**
     * @psalm-return EndpointFixtureOutput
     */
    public function load(
        ?AccessEnum $endpointUserAccessForSubject = AccessEnum::ALLOW,
        ?AccessEnum $endpointUserGroupAccessForSubject = AccessEnum::ALLOW,
        ?AccessEnum $endpointGroupUserAccessForSubject = AccessEnum::ALLOW,
        ?AccessEnum $endpointGroupUserGroupAccessForSubject = AccessEnum::ALLOW
    ): object {
        $user = TestEntityBuilder::buildUser();
        $this->em->persist($user);

        $userGroup = TestEntityBuilder::buildUserGroup();
        $userGroup->addUser($user);
        $this->em->persist($userGroup);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);

        $endpoint = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint);

        $this->em->flush();

        $endpointUserAccess = null;
        if (!is_null($endpointUserAccessForSubject)) {
            $endpointUserAccess = TestEntityBuilder::buildEndpointUserAccess(
                $endpoint,
                $user,
                ['relation' => $endpointUserAccessForSubject]
            );
            $this->em->persist($endpointUserAccess);
        }

        $endpointUserGroupAccess = null;
        if (!is_null($endpointUserGroupAccessForSubject)) {
            $endpointUserGroupAccess = TestEntityBuilder::buildEndpointUserGroupAccess(
                $endpoint,
                $userGroup,
                ['relation' => $endpointUserGroupAccessForSubject]
            );
            $this->em->persist($endpointUserGroupAccess);
        }

        $endpointGroupUserAccess = null;
        if (!is_null($endpointGroupUserAccessForSubject)) {
            $endpointGroupUserAccess = TestEntityBuilder::buildEndpointGroupUserAccess(
                $endpointGroup,
                $user,
                ['relation' => $endpointGroupUserAccessForSubject]
            );
            $this->em->persist($endpointGroupUserAccess);
        }

        $endpointGroupUserGroupAccess = null;
        if (!is_null($endpointGroupUserGroupAccessForSubject)) {
            $endpointGroupUserGroupAccess = TestEntityBuilder::buildEndpointGroupUserGroupAccess(
                $endpointGroup,
                $userGroup,
                ['relation' => $endpointGroupUserGroupAccessForSubject]
            );
            $this->em->persist($endpointGroupUserGroupAccess);
        }

        $this->em->flush();

        $entities = [
            'user' => $user,
            'userGroup' => $userGroup,
            'endpoint' => $endpoint,
            'endpointGroup' => $endpointGroup,
            'endpointUserAccess' => $endpointUserAccess,
            'endpointUserGroupAccess' => $endpointUserGroupAccess,
            'endpointGroupUserAccess' => $endpointGroupUserAccess,
            'endpointGroupUserGroupAccess' => $endpointGroupUserGroupAccess,
        ];

        foreach ($entities as $entity) {
            if (!is_null($entity)) {
                $this->em->refresh($entity);
            }
        }

        return (object)$entities;
    }
}
