# DESIGN - Application Backend

## Application Setup

- `make init` to initialize the development containers and dependencies
- `make up` to start the Docker development environment
    - `./scripts/application.sh` can be used to execute CLI commands
    - `./scripts/application.sh maintenance:cleanup` can be used to run regular maintenance tasks
      (executed automatically by the cron service)
- `make migrations` or `./scripts/doctrine.sh migrations:migrate --no-interaction` to run database migrations
    - `make new-migration` can be used to generate a new migration based in the changes made to entity definitions
- `./scripts/application.sh oauth2:genkeys` to generate OAuth keys
- `make` or `make check` can be used to run a number of static analysis, verification and test tools
    - `make cs` to run PHPCS alone
    - `make stan` to run PHPStan alone
    - `make validate-orm-mappings` to validate the Doctrine mapping definitions
    - `make test` to run all PHPUnit tests
    - `make test-unit` to only run PHPUnit unit tests
    - `make test-integration` to only run PHPUnit integration tests
- Application is available on `http://localhost/`, an endpoint that returns actual data is `http://localhost/version`
  (assuming you did not modify the `APACHE_HOST_PORT`)
- `make down` to stop the Docker development environment

## Release build & deployment

- `make build` to create a Docker release image that can be deployed to a server
    - the local working copy must be clean, otherwise the build will be aborted
    - the version number and additional details will be automatically generated and stored inside the image
    - the image will be named $COMPOSE_PROJECT_NAME-release
- `make up-release-test` to start a Docker test environment with the release Docker image
    - will use the same settings as the Docker development environment, so this must be stopped before
- `make down` to stop the Docker test environment
- `make remove-release-images` to remove any existing Docker release images
- To export the Docker release image (for example to deploy to a server)
    - first lookup the image ID using `docker image ls *release*`
    - export the image as tar using `docker save IMAGEID > image.tar`
      or compressed `docker save IMAGEID | gzip > image.tar.gz`

## Modules

### Main

Primary application initialization, exception logging and version details command/endpoint.
Publishes `\Application\Core\Util\Main\CleanupEvent` events that can be consumed by other modules to execute
regular maintenance tasks.

### Util/*

Additional module structure that contains modules not specific to the application domain/business logic.
Code in this modules must not depend on any application specific code, only external libraries.
