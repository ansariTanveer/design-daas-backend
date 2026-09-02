<?php

declare(strict_types=1);

namespace Application\Test;

use Application\Core\Desktop\Model\Desktop;
use Application\Core\Desktop\Model\DesktopGroup;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\Permissions\Model\Endpoint;
use Application\Core\Permissions\Model\EndpointGroup;
use Application\Core\Permissions\Model\EndpointGroupUserAccess;
use Application\Core\Permissions\Model\EndpointGroupUserGroupAccess;
use Application\Core\Permissions\Model\EndpointUserAccess;
use Application\Core\Permissions\Model\EndpointUserGroupAccess;
use Application\Core\User\Model\Admin;
use Application\Core\User\Model\BaseUser;
use Application\Core\User\Model\Password;
use Application\Core\User\Model\User;
use Application\Core\User\Model\UserGroup;
use Application\Core\Util\OAuth2\Model\PersistedAccessToken;
use Application\Core\Util\OAuth2\Model\PersistedClient;
use Application\Core\Util\OAuth2\Model\PersistedRefreshToken;
use BjoernGoetschke\DateTime\Interval;
use BjoernGoetschke\DateTime\Moment;
use RuntimeException;
use Throwable;

/**
 * @psalm-import-type httpMethod from Endpoint as endpointHttpMethod
 */
final class TestEntityBuilder
{
    private const SAMPLE_PASSWORD_HASH = '$2y$10$KaV8QI01JqM0NOAIg3x6Pe3SQRtGFw1/b33qRCarGqJY2O18FRiWO';

    /**
     * @param array{
     *     description?: string,
     * } $args
     */
    public static function buildUserGroup(array $args = []): UserGroup
    {
        return new UserGroup(
            $args['description'] ?? 'description_' . self::randomString()
        );
    }

    /**
     * @param array{
     *     description?: string,
     * } $args
     */
    public static function buildDesktop(array $args = []): Desktop
    {
        return new Desktop(
            $args['description'] ?? 'description_' . self::randomString()
        );
    }

    /**
     * @param array{
     *     name?: string,
     *     email?: string,
     *     password?: Password
     * } $args
     */
    public static function buildUser(array $args = []): User
    {
        return new User(
            $args['name'] ?? 'name_' . self::randomString(),
            $args['email'] ?? self::randomString() . '@example.com',
            $args['password'] ?? Password::fromPlainString(self::SAMPLE_PASSWORD_HASH),
        );
    }

    /**
     * @param array{
     *     name?: string,
     *     email?: string,
     *     password?: Password
     * } $args
     */
    public static function buildAdmin(array $args = []): Admin
    {
        return new Admin(
            $args['name'] ?? 'name_' . self::randomString(),
            $args['email'] ?? self::randomString() . '@example.com',
            $args['password'] ?? Password::fromPlainString(self::SAMPLE_PASSWORD_HASH),
        );
    }

    /**
     * @param array{
     *     identifier?: string,
     *     validUntilMoment?: Moment,
     *     clientIdentifier?: string,
     *     scopes?: array<string>,
     *     userIdentifier?: string|null,
     * } $args
     */
    public static function buildPersistedAccessToken(array $args = []): PersistedAccessToken
    {
        return new PersistedAccessToken(
            $args['identifier'] ?? 'identifier_' . self::randomString(),
            $args['validUntilMoment'] ?? Moment::now()->add(Interval::days(2)),
            $args['clientIdentifier'] ?? 'clientIdentifier_' . self::randomString(),
            $args['scopes'] ?? ['scope' . self::randomString()],
            $args['userIdentifier'] ?? 'userIdentifier_' . self::randomString()
        );
    }

    /**
     * @param array{
     *     identifier?: string,
     *     name?: string,
     *     redirectUris?: array<string>,
     *     isConfidential?: bool,
     *     secret?: string,
     * } $args
     */
    public static function buildPersistedClient(array $args = []): PersistedClient
    {
        return new PersistedClient(
            $args['identifier'] ?? 'identifier_' . self::randomString(),
            $args['name'] ?? 'name_' . self::randomString(),
            $args['redirectUris'] ?? ['http://example.com'],
            $args['isConfidential'] ?? true,
            $args['secret'] ?? 'secret_' . self::randomString(),
        );
    }

    /**
     * @param array{
     *     identifier?: string,
     *     validUntilMoment?: Moment,
     *     accessToken?: string,
     *     clientIdentifier?: string,
     *     scopes?: array<string>,
     *     userIdentifier?: string|null,
     * } $args
     */
    public static function buildPersistedRefreshToken(array $args = []): PersistedRefreshToken
    {
        return new PersistedRefreshToken(
            $args['identifier'] ?? 'identifier_' . self::randomString(),
            $args['validUntilMoment'] ?? Moment::now()->add(Interval::days(2)),
            $args['accessToken'] ?? 'token_' . self::randomString(),
            $args['clientIdentifier'] ?? 'clientIdentifier_' . self::randomString(),
            $args['scopes'] ?? ['scope' . self::randomString()],
            $args['userIdentifier'] ?? 'userIdentifier_' . self::randomString()
        );
    }

    /**
     * @param array{
     *     description?: string,
     * } $args
     */
    public static function buildDesktopGroup(array $args = []): DesktopGroup
    {
        return new DesktopGroup(
            $args['description'] ?? 'description_' . self::randomString()
        );
    }

    /**
     * @param array{
     *     uniqueGroupName?: non-empty-string
     * } $args
     */
    public static function buildEndpointGroup(array $args = []): EndpointGroup
    {
        return new EndpointGroup(
            $args['uniqueGroupName'] ?? 'id_' . self::randomString()
        );
    }

    /**
     * @psalm-param array{
     *     id?: int<1, 2147483647>,
     *     functionName?: non-empty-string,
     *     endpointUrl?: non-empty-string,
     *     method?: endpointHttpMethod
     * } $args
     */
    public static function buildEndpoint(EndpointGroup $endpointGroup,  $args = []): Endpoint
    {
        return new Endpoint(
            $args['id'] ?? self::randomUnsignedInt32(),
            $args['functionName'] ?? 'functionName_' . self::randomString(),
            $args['endpointUrl'] ?? 'endpointUrl_' . self::randomString(),
            $args['method'] ?? 'get',
            $endpointGroup
        );
    }

    /**
     * @param array{
     *     relation?: AccessEnum
     * } $args
     */
    public static function buildEndpointUserAccess(
        Endpoint $endpoint,
        BaseUser $user,
        array $args = []
    ): EndpointUserAccess {
        return new EndpointUserAccess(
            $endpoint,
            $user,
            $args['relation'] ?? AccessEnum::ALLOW
        );
    }

    /**
     * @param array{
     *     relation?: AccessEnum
     * } $args
     */
    public static function buildEndpointUserGroupAccess(
        Endpoint $endpoint,
        UserGroup $userGroup,
        array $args = []
    ): EndpointUserGroupAccess {
        return new EndpointUserGroupAccess(
            $endpoint,
            $userGroup,
            $args['relation'] ?? AccessEnum::ALLOW
        );
    }

    /**
     * @param array{
     *     endpointGroup?: EndpointGroup,
     *     user?: BaseUser,
     *     relation?: AccessEnum
     * } $args
     */
    public static function buildEndpointGroupUserAccess(
        EndpointGroup $endpointGroup,
        BaseUser $baseUser,
        array $args = []
    ): EndpointGroupUserAccess {
        return new EndpointGroupUserAccess(
            $endpointGroup,
            $baseUser,
            $args['relation'] ?? AccessEnum::ALLOW
        );
    }

    /**
     * @param array{
     *     relation?: AccessEnum
     * } $args
     */
    public static function buildEndpointGroupUserGroupAccess(
        EndpointGroup $endpointGroup,
        UserGroup $userGroup,
        array $args = []
    ): EndpointGroupUserGroupAccess {
        return new EndpointGroupUserGroupAccess(
            $endpointGroup,
            $userGroup,
            $args['relation'] ?? AccessEnum::ALLOW
        );
    }

    private static function randomString(): string
    {
        try {
            return md5(random_bytes(8));
        } catch (Throwable $t) {
            // to avoid adding phpdoc @throws everywhere
            throw new RuntimeException($t->getMessage(), 0, $t);
        }
    }

    /**
     * @return int<1, 2147483647>
     */
    private static function randomUnsignedInt32(): int
    {
        return rand(1, 2147483647);
    }
}
