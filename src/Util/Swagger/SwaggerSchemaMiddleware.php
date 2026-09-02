<?php

namespace Application\Core\Util\Swagger;

use Application\Common\HttpBaseUrl\BaseUrlResolverInterface;
use Application\Common\HttpResponse\HttpResponseFactory;
use Assert\Assert;
use cebe\openapi\spec\Server;
use League\OpenAPIValidation\PSR7\Exception\NoPath;
use League\OpenAPIValidation\PSR7\Exception\Validation\InvalidSecurity;
use League\OpenAPIValidation\PSR7\Exception\ValidationFailed;
use League\OpenAPIValidation\Schema\BreadCrumb;
use League\OpenAPIValidation\Schema\Exception\SchemaMismatch;
use LogicException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

final readonly class SwaggerSchemaMiddleware
{
    public function __construct(
        private ContainerInterface $container,
        private LoggerInterface $logger,
        private BaseUrlResolverInterface $baseUrlResolver,
        private HttpResponseFactory $responseFactory,
        private SwaggerDefinitionCache $definitionCache,
        private string $developmentModeKey
    ) {
    }

    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        $operation = null;

        try {
            $validateRequest = $request;

            /*
             * Workaround because the validation library does not decode
             * JSON in a multipart body correctly (or at all ...)
             */
            $contentType = $request->getHeaderLine('Content-Type');
            $isMultiPart = stripos($contentType, 'multipart/form-data') === 0;
            if ($isMultiPart) {
                $bodyParts = [];
                foreach ((array)$request->getParsedBody() as $name => $part) {
                    if (is_string($part) && stripos(ltrim($part), '{') === 0) {
                        $bodyParts[$name] = json_decode($part, true);
                    } else {
                        $bodyParts[$name] = $part;
                    }
                }
                $validateRequest = $request->withParsedBody($bodyParts);
            }

            $validator = $this->definitionCache->loadRequestValidator();
            $originalServers = $validator->getSchema()->servers;
            try {
                $validator->getSchema()->servers = [
                    new Server(
                        [
                            'url' => (string)$this->baseUrlResolver->baseUrl(),
                        ]
                    )
                ];
                $operation = $validator->validate($validateRequest);
            } catch (LogicException $exception) {
                return $this->responseFactory->buildJsonMessage(
                    400,
                    sprintf('Invalid request: %1$s', $exception->getMessage())
                );
            } finally {
                $validator->getSchema()->servers = $originalServers;
            }
        } catch (NoPath $exception) {
            // do not validate actions that are not defined in swagger
        } catch (InvalidSecurity $exception) {
            return $this->handleInvalidRequestSecurityException($exception);
        } catch (ValidationFailed $exception) {
            return $this->handleRequestValidationFailedException($exception);
        }

        $response = $next($request);

        if ($operation !== null) {
            try {
                $validator = $this->definitionCache->loadResponseValidator();
                $originalServers = $validator->getSchema()->servers;
                try {
                    $validator->getSchema()->servers = [
                        new Server(
                            [
                                'url' => (string)$this->baseUrlResolver->baseUrl(),
                            ]
                        )
                    ];
                    $this->definitionCache->loadResponseValidator()->validate($operation, $response);
                } finally {
                    $validator->getSchema()->servers = $originalServers;
                }
            } catch (ValidationFailed $exception) {
                $this->handleResponseValidationFailedException($exception);
            }
        }

        return $response;
    }

    private function handleInvalidRequestSecurityException(InvalidSecurity $exception): ResponseInterface
    {
        $message = sprintf(
            'Access denied: %1$s',
            $exception->getMessage()
        );

        return $this->responseFactory->buildJsonMessage(
            403,
            $message
        );
    }

    private function handleRequestValidationFailedException(ValidationFailed $exception): ResponseInterface
    {
        $message = $this->formatValidationFailedException($exception);

        $this->logger->debug($message);

        return $this->responseFactory->buildJsonMessage(
            400,
            $message
        );
    }

    private function handleResponseValidationFailedException(ValidationFailed $exception): void
    {
        $message = $this->formatValidationFailedException($exception);

        $this->logger->warning($message);

        if ($this->container->get($this->developmentModeKey) === true) {
            throw new LogicException($message, 0, $exception);
        }
    }

    private function formatValidationFailedException(ValidationFailed $exception): string
    {
        $breadCrumb = [null];
        $messages = [];
        do {
            $messages[] = $exception->getMessage();
            if ($exception instanceof SchemaMismatch) {
                assert($exception->dataBreadCrumb() instanceof BreadCrumb);
                Assert::that($exception->dataBreadCrumb())->notNull();
                $breadCrumb = $exception->dataBreadCrumb()->buildChain();
            }
        } while (null !== $exception = $exception->getPrevious());

        $breadCrumb = array_map(
            function ($v): string {
                return (is_string($v) && strlen($v) > 0) ? $v : '?';
            },
            $breadCrumb
        );

        return sprintf(
            'Schema validation failed @ %1$s: %2$s',
            implode('.', $breadCrumb),
            implode(': ', $messages)
        );
    }
}
