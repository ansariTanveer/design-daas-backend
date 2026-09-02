<?php

namespace Application\Core\Util\Swagger;

use Assert\Assert;
use cebe\openapi\spec\OpenApi as OpenApiSchema;
use League\OpenAPIValidation\PSR7\ResponseValidator;
use League\OpenAPIValidation\PSR7\SchemaFactory\JsonFactory;
use League\OpenAPIValidation\PSR7\ServerRequestValidator;
use OpenApi\Annotations\OpenApi as OpenApiDefinitions;
use OpenApi\Generator;
use Psr\Http\Message\UriInterface;
use stdClass;

final class SwaggerDefinitionCache
{
    private ?OpenApiDefinitions $inMemoryDefinitionsCache = null;

    private ?OpenApiSchema $inMemorySchemaCache = null;

    public function __construct(
        private readonly ?string $staticCacheDir,
        private readonly string $definitionsCacheFileName,
        private readonly string $schemaCacheFileName,
        private readonly string $searchPath
    ) {
    }

    // Must be called in scripts/build-cache.php
    public function buildCache(): void
    {
        if (!$this->useCache() || is_null($this->staticCacheDir)) {
            return;
        }
        $definitions = $this->buildOpenApiDefinitions();
        $schema = $this->buildOpenApiSchema($definitions);
        if (!(file_exists($this->staticCacheDir) && is_writable($this->staticCacheDir))) {
            mkdir($this->staticCacheDir);
        }
        file_put_contents($this->definitionsCacheFile(), serialize($definitions));
        file_put_contents($this->schemaCacheFile(), serialize($schema));
    }

    private function useCache(): bool
    {
        return $this->staticCacheDir !== null;
    }

    private function definitionsCacheFile(): string
    {
        return sprintf('%1$s/%2$s', $this->staticCacheDir, $this->definitionsCacheFileName);
    }

    private function schemaCacheFile(): string
    {
        return sprintf('%1$s/%2$s', $this->staticCacheDir, $this->schemaCacheFileName);
    }

    public function loadRequestValidator(): ServerRequestValidator
    {
        return new ServerRequestValidator($this->loadOpenApiSchema());
    }

    public function loadResponseValidator(): ResponseValidator
    {
        return new ResponseValidator($this->loadOpenApiSchema());
    }

    public function loadJson(string $apiVersion, UriInterface $baseUrl): stdClass
    {
        $baseUrl = (string)$baseUrl;
        $definitionsObject = $this->loadOpenApiDefinitions();
        $definitions = $definitionsObject->jsonSerialize();
        assert($definitions instanceof stdClass);
        Assert::that($definitions)->isInstanceOf(stdClass::class);

        $hasVersion = isset($definitions->info) &&
            isset($definitions->info->version) &&
            is_string($definitions->info->version);
        if ($hasVersion) {
            if ($definitions->info->version === 'API_VERSION') {
                $definitions->info->version = $apiVersion;
            }
        }

        $hasServers = isset($definitions->servers) &&
            is_array($definitions->servers);
        if ($hasServers) {
            foreach ($definitions->servers as $server) {
                if (!isset($server->url) || !is_string($server->url)) {
                    continue;
                }
                if (str_starts_with($server->url, 'REQUEST_BASE_URL')) {
                    $server->url = preg_replace('=REQUEST_BASE_URL=', $baseUrl, $server->url, 1);
                }
            }
        }

        $hasSecuritySchemes = isset($definitions->components) &&
            isset($definitions->components->securitySchemes) &&
            is_array($definitions->components->securitySchemes);
        if ($hasSecuritySchemes) {
            foreach ($definitions->components->securitySchemes as $securityScheme) {
                if (!is_array($securityScheme->flows)) {
                    continue;
                }

                foreach ($securityScheme->flows as $flow) {
                    if (!isset($flow->tokenUrl) || !is_string($flow->tokenUrl)) {
                        continue;
                    }
                    if (str_starts_with($flow->tokenUrl, 'REQUEST_BASE_URL')) {
                        $flow->tokenUrl = preg_replace('=REQUEST_BASE_URL=', $baseUrl, $flow->tokenUrl, 1);
                    }
                }
            }
        }

        return $definitions;
    }

    private function loadOpenApiSchema(): OpenApiSchema
    {
        if ($this->inMemorySchemaCache === null) {
            if ($this->useCache()) {
                $cachedSchema = unserialize((string)file_get_contents($this->schemaCacheFile()));
                assert($cachedSchema instanceof OpenApiSchema);
                Assert::that($cachedSchema)->isInstanceOf(OpenApiSchema::class);
                $this->inMemorySchemaCache = $cachedSchema;
            } else {
                $this->inMemorySchemaCache = $this->buildOpenApiSchema($this->loadOpenApiDefinitions());
            }
        }
        return $this->inMemorySchemaCache;
    }

    private function buildOpenApiSchema(?OpenApiDefinitions $definitions): OpenApiSchema
    {
        $factory = new JsonFactory(is_null($definitions) ? '' : $definitions->toJson());
        return $factory->createSchema();
    }

    private function loadOpenApiDefinitions(): OpenApiDefinitions
    {
        if ($this->inMemoryDefinitionsCache === null) {
            if ($this->useCache()) {
                $cachedDefinitions = unserialize((string)file_get_contents($this->definitionsCacheFile()));
                assert($cachedDefinitions instanceof OpenApiDefinitions);
                Assert::that($cachedDefinitions)->isInstanceOf(OpenApiDefinitions::class);
                $this->inMemoryDefinitionsCache = $cachedDefinitions;
            } else {
                $this->inMemoryDefinitionsCache = $this->buildOpenApiDefinitions();
            }
        }
        assert(!is_null($this->inMemoryDefinitionsCache));
        return $this->inMemoryDefinitionsCache;
    }

    private function buildOpenApiDefinitions(): ?OpenApiDefinitions
    {
        return Generator::scan(
            [
                $this->searchPath,
            ]
        );
    }
}
