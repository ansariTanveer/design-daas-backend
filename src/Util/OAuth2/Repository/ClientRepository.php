<?php

namespace Application\Core\Util\OAuth2\Repository;

use Application\Common\DoctrineORM\InjectableEntityRepository;
use Application\Core\Util\OAuth2\Model\PersistedClient;
use Application\Core\Util\OAuth2\OAuth2Entity\OAuth2ClientEntity;
use Assert\Assert;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ClientRepositoryInterface;

/**
 * @extends InjectableEntityRepository<PersistedClient>
 * @template-extends InjectableEntityRepository<PersistedClient>
 */
class ClientRepository extends InjectableEntityRepository implements ClientRepositoryInterface
{
    public function getClientEntity(/* string */ $clientIdentifier): ?ClientEntityInterface
    {
        Assert::that($clientIdentifier)->string();

        $client = $this->find($clientIdentifier);
        if ($client === null) {
            return null;
        }

        $clientEntity = new OAuth2ClientEntity();
        $clientEntity->setIdentifier($client->identifier());
        $clientEntity->setName($client->name());
        $clientEntity->setRedirectUri($client->redirectUris());
        $clientEntity->setIsConfidential($client->isConfidential());

        return $clientEntity;
    }

    public function validateClient(
        /* string */ $clientIdentifier,
        /* ?string */ $clientSecret,
        /* ?string */ $grantType
    ): bool {
        Assert::that($clientIdentifier)->string();
        Assert::that($clientSecret)->nullOr()->string();
        Assert::that($grantType)->nullOr()->string();

        $client = $this->find($clientIdentifier);
        if ($client === null) {
            return false;
        }

        if(!$client->isConfidential()) {
            return true;
        }

        return $client->verifySecret($clientSecret);
    }
}
