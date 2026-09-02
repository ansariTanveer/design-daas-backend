<?php

declare(strict_types=1);

namespace Application\Core\Util\OAuth2;

use Application\Core\Util\OAuth2\Exception\OAuth2AccessControlMiddlewareException;
use Assert\Assert;
use Doctrine\Common\Annotations\AnnotationReader;
use Http\Factory\Guzzle\ResponseFactory;
use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use OpenApi\Annotations\Operation;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionMethod;

/**
 * The "league/openapi-psr7-validator" package does currently
 * (2020-10-21, version 0.12.1) not validate OAuth security schemes.
 *
 * This implementation assumes the defined schemes are OAuth security
 * schemes and ONLY validates annotations on the action, NOT possibly
 * existing ones of the root level of the OpenAPI definitions.
 *
 * @see https://github.com/thephpleague/openapi-psr7-validator/issues/4
 */
final readonly class OAuth2AccessControlMiddleware
{
    /**
     * @param ContainerInterface $container
     * @param string $routeNameRequestAttribute
     * @param AuthorizationValidatorInterface[]|mixed[] $knownSchemes
     *        Key-Value-Pairs of swagger security scheme names and authorization validators
     *            (either the validator itself or the name to retrieve it from the container)
     */
    public function __construct(
        private ContainerInterface $container,
        private ResponseFactory $responseFactory,
        private string $routeNameRequestAttribute,
        private array $knownSchemes
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @param callable $next
     * @return ResponseInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \ReflectionException
     * @throws OAuth2AccessControlMiddlewareException
     */
    public function __invoke(
        ServerRequestInterface $request,
        callable $next
    ): ResponseInterface {
        $route = $this->routeOfRequest($request);
        $targetAction = new ReflectionMethod($route['className'], $route['methodName']);
        $endpointConfiguration = $this->endpointConfiguration($targetAction);

        if (is_null($endpointConfiguration)) {
            // Action is not configured and therefore accessible to everyone
            return $next($request);
        }

        if (strtolower($endpointConfiguration->method) !== strtolower($request->getMethod())) {
            // Configuration is for a different method (GET/POST/PUT) then the request is
            throw OAuth2AccessControlMiddlewareException::actionMethodMismatch(
                $endpointConfiguration->method,
                $request->getMethod()
            );
        }

        /** @var OAuthServerException[] $authErrors */
        $authErrors = [];

        $acceptedScopesBySchema = $this->acceptedScopesBySchemaForOperation($endpointConfiguration);
        if (count($acceptedScopesBySchema) < 1) {
            // If no schemas are accepted by the endpoint it is accessible by anyone
            return $next($request);
        }

        // If schemas are configured, we need to find one that grants the user access
        foreach ($acceptedScopesBySchema as $schema => $scopes) {
            if (!array_key_exists($schema, $this->knownSchemes)) {
                // Schema name is unknown (did you make a typo?)
                throw OAuth2AccessControlMiddlewareException::unknownSchema($schema);
            }

            $authorizationValidator = $this->knownSchemes[$schema];
            if (is_string($authorizationValidator)) {
                $authorizationValidator = $this->container->get($authorizationValidator);
            }
            if (!$authorizationValidator instanceof AuthorizationValidatorInterface) {
                // No code found to tackle this schema
                throw OAuth2AccessControlMiddlewareException::authorizationValidatorLoadFailed($schema);
            }

            // run schema
            try {
                $authorizedRequest = $authorizationValidator->validateAuthorization($request);
            } catch (OAuthServerException $exception) {
                $authErrors[] = $exception;
                continue;
            }

            // If these points are set, schema granted access
            $requestAttributes = $authorizedRequest->getAttributes();
            $authorizationAttributesSet =
                array_key_exists('oauth_access_token_id', $requestAttributes) &&
                array_key_exists('oauth_client_id', $requestAttributes) &&
                array_key_exists('oauth_user_id', $requestAttributes) &&
                array_key_exists('oauth_scopes', $requestAttributes);
            if (!$authorizationAttributesSet) {
                continue;
            }

            Assert::that($requestAttributes['oauth_access_token_id'])->nullOr()->string();
            Assert::that($requestAttributes['oauth_client_id'])->nullOr()->string();
            Assert::that($requestAttributes['oauth_user_id'])->nullOr()->string();
            Assert::thatAll($requestAttributes['oauth_scopes'])->string();
            /** @var array{
             *     oauth_access_token_id: string|null,
             *     oauth_client_id: string|null,
             *     oauth_user_id: string|null,
             *     oauth_scopes: array<string>
             * } $requestAttributes
             */

            // If scopes are configured for this schema, grant access only if at least one scope matches
            // with the scopes for the user
            $commonScopes = array_intersect($scopes, $requestAttributes['oauth_scopes']);
            if (count($scopes) > 0 && count($commonScopes) < 1) {
                $authErrors[] = $this->endpointAccessDeniedException(
                    sprintf(
                        'Invalid scopes, need one of "%1$s" but the token only contains "%2$s"',
                        implode(',', $scopes),
                        implode(',', $requestAttributes['oauth_scopes'])
                    )
                );
                continue;
            }

            // Validation successful, client is authorized to use the endpoint.
            $request = $authorizedRequest->withAttribute('oauth_security_scheme', $schema);
            return $next($request);
        }

        // No schema warrants access
        $authErrors[] = $this->endpointAccessDeniedException(
            sprintf(
                'Not authorized for any of the accepted security schemes: %1$s',
                implode(',', array_keys($acceptedScopesBySchema))
            )
        );

        /** @var OAuthServerException $firstException */
        $firstException = reset($authErrors);
        return $firstException->generateHttpResponse($this->responseFactory->createResponse(500));
    }

    /**
     * [ 'scheme1' => [ 'scope1', 'scope2' ], 'scheme2' => [ 'scope3' ] ]
     * @return array<string, array<string>>
     */
    private function acceptedScopesBySchemaForOperation(Operation $operation): array
    {
        $acceptedSchemesAndScopes = [];

        if (!is_array($operation->security)) {
            return $acceptedSchemesAndScopes;
        }

        foreach ($operation->security as $securityDefinition) {
            if (!is_array($securityDefinition)) {
                continue;
            }

            foreach ($securityDefinition as $definitionName => $scopes) {
                if (!is_array($scopes)) {
                    continue;
                }

                if (!array_key_exists($definitionName, $acceptedSchemesAndScopes)) {
                    $acceptedSchemesAndScopes[$definitionName] = [];
                }

                foreach ($scopes as $scope) {
                    if (is_string($scope)) {
                        $acceptedSchemesAndScopes[$definitionName][] = $scope;
                    }
                }
            }
        }

        return array_map(
            function (array $scopes): array {
                return array_unique($scopes);
            },
            $acceptedSchemesAndScopes
        );
    }

    private function endpointAccessDeniedException(string $hint): OAuthServerException
    {
        return new OAuthServerException(
            'Not allowed to access the requested endpoint (but the access token was valid)',
            100,
            'insufficient_permissions',
            403,
            $hint
        );
    }

    /**
     * @return array{className: string, methodName: string}
     * @throws OAuth2AccessControlMiddlewareException
     * Extracts what action in what controller $request is going to (info is injected by earlier middleware)
     */
    private function routeOfRequest(ServerRequestInterface $request): array
    {
        $routeName = $request->getAttribute($this->routeNameRequestAttribute);
        if (!is_string($routeName)) {
            throw OAuth2AccessControlMiddlewareException::routeParsingFailed();
        }

        // Route format is Full\Namespace\Path\ClassName::MethodName
        $routeParts = explode('::', $routeName, 2);
        if (count($routeParts) != 2) {
            throw OAuth2AccessControlMiddlewareException::routeParsingFailed();
        }

        return ['className' => $routeParts[0], 'methodName' => $routeParts[1]];
    }

    /**
     * @throws OAuth2AccessControlMiddlewareException
     * Returns the OA\Get / OA\Put / ... attribute of $reflectionMethod
     */
    private function endpointConfiguration(ReflectionMethod $reflectionMethod): ?Operation
    {
        $attributeOptions = [];
        foreach ($reflectionMethod->getAttributes() as $attribute) {
            $attributeInstance = $attribute->newInstance();
            if ($attributeInstance instanceof Operation) {
                $attributeOptions[] = $attributeInstance;
            }
        }

        $annotationOptions = [];
        $annotationReader = new AnnotationReader();
        foreach ($annotationReader->getMethodAnnotations($reflectionMethod) as $annotation) {
            if ($annotation instanceof Operation) {
                $annotationOptions[] = $annotation;
            }
        }

        $options = array_merge($attributeOptions, $annotationOptions);
        if (count($options) === 0) {
            return null;
        } elseif (count($options) > 1) {
            throw OAuth2AccessControlMiddlewareException::tooManyConfigurations();
        }

        return $options[0];
    }

    /** @phpstan-ignore-next-line */
    public function getKnownSchemes(): array
    {
        return $this->knownSchemes;
    }
}
