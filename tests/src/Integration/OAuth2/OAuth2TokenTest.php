<?php

declare(strict_types=1);

namespace Application\Test\Integration\OAuth2;

use Application\Common\Application\Http\HttpApplication;
use Application\Common\Application\TestHelper\TestHttpRequestBuilder;
use Application\Core\Permissions\DTO\PermissionRequestDTO;
use Application\Core\Permissions\DTO\PermissionResultDTO;
use Application\Core\Permissions\Enum\AccessEnum;
use Application\Core\User\Model\Password;
use Application\Core\User\Model\User;
use Application\Core\Util\OAuth2\Model\PersistedClient;
use Application\Test\TestApplicationFactory;
use Application\Test\TestCase;
use Application\Test\TestEntityBuilder;
use Doctrine\ORM\EntityManagerInterface;

final class OAuth2TokenTest extends TestCase
{
    private HttpApplication $application;

    private PersistedClient $persistedClient;

    private User $user;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        parent::setUp();

        $this->application = TestApplicationFactory::http();
        TestApplicationFactory::injectDatabaseConnection($this->application);

        $this->em = TestApplicationFactory::extractEntityManager($this->application);

        $this->user = TestEntityBuilder::buildUser([
            'password' => Password::fromPlainString('!Swordfish1234!'),
        ]);
        $this->user->enable();
        $this->em->persist($this->user);

        // create OAuth client
        $this->persistedClient = new PersistedClient(
            bin2hex(random_bytes(20)), // 20 bytes = 40 hex characters
            'oauth2_login_test',
            [],
            true,
            bin2hex(random_bytes(40)), // 40 bytes = 80 hex characters
        );
        $this->em->persist($this->persistedClient);
        $this->em->flush();

        $this->em->clear();
    }

    public function testUserLoginProcess(): void
    {
        $postData = json_encode(
            [
                'grant_type' => 'password',
                'scope' => 'user',
                'username' => $this->user->email(),
                'password' => '!Swordfish1234!',
                'client_id' => $this->persistedClient->identifier(),
                'client_secret' => $this->persistedClient->secret(),
            ]
        );
        assert(is_string($postData));

        $response = (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/oauth2/user/token')
            ->body($postData)
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);


        $jsonResponse = json_decode($response->bodyAsString(), true);
        assert(is_array($jsonResponse));

        self::assertArrayHasKey('token_type', $jsonResponse);
        self::assertArrayHasKey('expires_in', $jsonResponse);
        self::assertArrayHasKey('access_token', $jsonResponse);
        self::assertArrayHasKey('refresh_token', $jsonResponse);

        self::assertEquals('Bearer', $jsonResponse['token_type']);

        $accessToken = $jsonResponse['access_token'];

        // No flush here to see if the endpoint did this properly
        $this->em->clear();

        // test the token
        (new TestHttpRequestBuilder())
            ->method('GET')
            ->uri('/ping/user')
            ->additionalServer(['HTTP_AUTHORIZATION' => 'Bearer ' . $accessToken])
            ->expectResponseCode(200)
            ->execute($this->application);
    }

    public function testRejectsBadPassword(): void
    {
        $postData = json_encode(
            [
                'grant_type' => 'password',
                'scope' => 'user',
                'username' => $this->user->email(),
                'password' => '!Manatee1234!',
                'client_id' => $this->persistedClient->identifier(),
                'client_secret' => $this->persistedClient->secret(),
            ]
        );
        assert(is_string($postData));

        (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/oauth2/user/token')
            ->body($postData)
            ->contentType('application/json')
            ->expectResponseCode(400)
            ->execute($this->application);
    }

    public function testRejectsScopeNotAllowedForUser(): void
    {

        $postData = json_encode(
            [
                'grant_type' => 'password',
                'scope' => 'admin',
                'username' => $this->user->email(),
                'password' => '!Swordfish1234!',
                'client_id' => $this->persistedClient->identifier(),
                'client_secret' => $this->persistedClient->secret(),
            ]
        );
        assert(is_string($postData));

        (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/oauth2/user/token')
            ->body($postData)
            ->contentType('application/json')
            ->expectResponseCode(401)
            ->execute($this->application);
    }

    public function testLoginWithEndpointCheckPermissions(): void
    {
        $user = TestEntityBuilder::buildUser([
            'password' => Password::fromPlainString('!Swordfish1234!'),
        ]);
        $user->enable();
        $this->em->persist($user);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);
        $endpoint1 = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint1);
        $endpoint2 = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint2);

        $permission = TestEntityBuilder::buildEndpointUserAccess(
            $endpoint1,
            $user,
            ['relation' => AccessEnum::ALLOW]
        );
        $this->em->persist($permission);
        $permission = TestEntityBuilder::buildEndpointUserAccess(
            $endpoint2,
            $user,
            ['relation' => AccessEnum::DENY]
        );
        $this->em->persist($permission);
        $this->em->flush();

        $credentials = [
            'grant_type' => 'password',
            'scope' => 'user',
            'username' => $user->email(),
            'password' => '!Swordfish1234!',
            'client_id' => $this->persistedClient->identifier(),
            'client_secret' => $this->persistedClient->secret(),
            'endpoint' => $endpoint2->functionName(),
        ];
        $postData = json_encode($credentials);
        assert(is_string($postData));

        // With a denied endpoint
        (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/oauth2/user/token')
            ->body($postData)
            ->contentType('application/json')
            ->expectResponseCode(406)
            ->execute($this->application);

        $credentials['endpoint'] = $endpoint1->functionName();
        $postData = json_encode($credentials);
        assert(is_string($postData));

        // With an allowed endpoint
        (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/oauth2/user/token')
            ->body($postData)
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);
    }

    public function testTokenInformation(): void
    {
        $admin = TestEntityBuilder::buildAdmin([
            'password' => Password::fromPlainString('!Swordfish1234!'),
        ]);
        $admin->enable();
        $this->em->persist($admin);
        $user = TestEntityBuilder::buildUser(
            [
                'password' => Password::fromPlainString('!Swordfish1234!'),
            ],
        );
        $user->enable();
        $this->em->persist($user);

        $endpointGroup = TestEntityBuilder::buildEndpointGroup();
        $this->em->persist($endpointGroup);
        $endpoint1 = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint1);
        $endpoint2 = TestEntityBuilder::buildEndpoint($endpointGroup);
        $this->em->persist($endpoint2);

        $permission = TestEntityBuilder::buildEndpointUserAccess(
            $endpoint1,
            $user,
            ['relation' => AccessEnum::ALLOW]
        );
        $this->em->persist($permission);
        $permission = TestEntityBuilder::buildEndpointUserAccess(
            $endpoint2,
            $user,
            ['relation' => AccessEnum::DENY]
        );
        $this->em->persist($permission);
        $this->em->flush();

        $credentials = [
            'grant_type' => 'password',
            'scope' => 'user',
            'username' => $user->email(),
            'password' => '!Swordfish1234!',
            'client_id' => $this->persistedClient->identifier(),
            'client_secret' => $this->persistedClient->secret(),
        ];
        $postData = json_encode($credentials);
        assert(is_string($postData));

        // Normal login of the user to get the access token
        $loginResponse = (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/oauth2/user/token')
            ->body($postData)
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);
        $json = json_decode($loginResponse->bodyAsString());
        self::assertInstanceOf(\stdClass::class, $json);
        self::assertNotEmpty($json->access_token);
        $token = $json->access_token;

        // With wrong token
        $requestDto = new PermissionRequestDTO();
        $requestDto->token = 'rubbish';
        $requestDto->function_name = $endpoint1->functionName();
        $json = json_encode($requestDto);
        assert(is_string($json));

        $response = (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/permissions_info')
            ->additionalServer(TestCase::authorizeAdmin($admin))
            ->body($json)
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);
        $json = json_decode($response->bodyAsString());
        self::assertInstanceOf(\stdClass::class, $json);
        $responseDto = PermissionResultDTO::fromJson($json);
        self::assertEquals(AccessEnum::DENY->value, $responseDto->result);

        // With a denied endpoint
        $requestDto->token = $token;
        $requestDto->function_name = $endpoint2->functionName();
        $json = json_encode($requestDto);
        assert(is_string($json));

        (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/permissions_info')
            ->additionalServer(TestCase::authorizeAdmin($admin))
            ->body($json)
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);
        $json = json_decode($response->bodyAsString());
        self::assertInstanceOf(\stdClass::class, $json);
        $responseDto = PermissionResultDTO::fromJson($json);
        self::assertEquals(AccessEnum::DENY->value, $responseDto->result);

        // With an allowed endpoint
        $requestDto->function_name = $endpoint1->functionName();
        $json = json_encode($requestDto);
        assert(is_string($json));

        $response = (new TestHttpRequestBuilder())
            ->method('POST')
            ->uri('/permissions_info')
            ->additionalServer(TestCase::authorizeAdmin($admin))
            ->body($json)
            ->contentType('application/json')
            ->expectResponseCode(200)
            ->execute($this->application);
        $json = json_decode($response->bodyAsString());
        self::assertInstanceOf(\stdClass::class, $json);
        $responseDto = PermissionResultDTO::fromJson($json);
        self::assertEquals(AccessEnum::ALLOW->value, $responseDto->result);
    }
}
