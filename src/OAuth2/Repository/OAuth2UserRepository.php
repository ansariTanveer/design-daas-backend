<?php

namespace Application\Core\OAuth2\Repository;

use Application\Core\User\Exception\InvalidPasswordException;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\Password;
use Application\Core\User\Model\User;
use Application\Core\User\Repository\UserRepository;
use Application\Core\Util\OAuth2\OAuth2Entity\OAuth2UserEntity;
use Application\Core\Util\OAuth2\VerifyingUserRepositoryInterface;
use Assert\Assert;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;

final readonly class OAuth2UserRepository implements VerifyingUserRepositoryInterface
{
    private const IDENTIFIER_TYPE = 'user_id';

    public function __construct(
        private UserRepository $repository
    ) {
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $grantType
     */
    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity
    ): ?UserEntityInterface {
        Assert::that($username)->string();
        Assert::that($password)->string();
        Assert::that($grantType)->string();

        $user = $this->repository->findBaseUserByEmail($username);
        if (is_null($user)) {
            return null;
        }

        try {
            if (!$user->password()->equals(Password::fromPlainString($password))) {
                return null;
            }
        } catch (InvalidPasswordException $e) {
            return null;
        }

        $userEntity = new OAuth2UserEntity();
        $userEntity->setIdentifier(json_encode([self::IDENTIFIER_TYPE, $user->id()]));

        return $userEntity;
    }

    public function verifyUserIdentifier(string $identifier): bool
    {
        $id = self::extractUserIdFromIdentifier($identifier);
        assert(is_int($id));
        $user = $this->repository->findBaseUserById($id);

        return ($user instanceof BaseUser);
    }

    public static function extractUserIdFromIdentifier(string $identifier): ?int
    {
        $identifier = json_decode($identifier);
        if (!is_array($identifier)) {
            return null;
        }

        $userType = array_shift($identifier);
        $userId = array_shift($identifier);

        if ($userType !== self::IDENTIFIER_TYPE || !is_int($userId)) {
            return null;
        }

        return $userId;
    }
}
