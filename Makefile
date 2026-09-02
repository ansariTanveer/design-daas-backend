include .env

#
# Fixed versions to use
#
php_version = 8.2.14-apache
composer_version = 2.6.6
xdebug_version = 3.3.1
yacron_version = 0.19.0
mariadb_version = 10.11.6

#
# Default target, will run when executing make without arguments
#
.PHONY: check
check: cs stan validate-orm-mappings test

#
# General
#
.PHONY: init
init: docker-init project-init auth.json vendor

.PHONY: restart
restart: down init up

.PHONY: dist-clean
dist-clean: docker-clean project-clean vendor-clean

.PHONY: dist-fresh
dist-fresh: dist-clean init

#
# Tools
#
.PHONY: cs
cs: vendor
	@./scripts/phpcs.sh

.PHONY: stan
stan: vendor
	@./scripts/phpstan.sh analyse

.PHONY: validate-orm-mappings
validate-orm-mappings: vendor
	@./scripts/doctrine.sh orm:validate-schema --skip-sync

.PHONY: test
test: vendor
	@./scripts/phpunit.sh

.PHONY: test-unit
test-unit: vendor
	@./scripts/phpunit.sh --testsuite "Unit"

.PHONY: test-integration
test-integration: vendor
	@./scripts/phpunit.sh --testsuite "Integration"

.PHONY: testv
testv: vendor
	@./scripts/phpunit.sh --teamcity

#
# Build
#
.PHONY: build
build:
	@./scripts/shell.sh php ./scripts/check-clean-working-directory.php
	@$(MAKE) build-release-image

.PHONY: build-release-image
build-release-image: export BUILD_IMAGE_NAME=${COMPOSE_PROJECT_NAME}-release
build-release-image: export BUILD_IMAGE_ID_FILE=./release-image-id.build
build-release-image:
	@docker build \
		--progress=plain \
		--pull \
		--build-arg PHP_VERSION=${php_version} \
		--build-arg COMPOSER_VERSION=${composer_version} \
		--build-arg XDEBUG_VERSION=${xdebug_version} \
		--build-arg YACRON_VERSION=${yacron_version} \
		--iidfile "${BUILD_IMAGE_ID_FILE}" \
		-f "./docker/php/apache/Dockerfile" \
		--target release \
		./
	@docker tag \
		"`cat "${BUILD_IMAGE_ID_FILE}"`" \
		"${BUILD_IMAGE_NAME}:`docker run --rm \
			--entrypoint docker-php-entrypoint \
			\`cat "${BUILD_IMAGE_ID_FILE}"\` cat /project/version_tag.txt`"
	@docker tag \
		"`cat "${BUILD_IMAGE_ID_FILE}"`" \
		"${BUILD_IMAGE_NAME}:latest"
	@echo "Image ID = `cat "${BUILD_IMAGE_ID_FILE}"`"
	@rm -rf ./*.build
	@docker image ls "${BUILD_IMAGE_NAME}"

.PHONY: remove-release-images
remove-release-images:
	@./scripts/remove-images.sh "${COMPOSE_PROJECT_NAME}-release"

#
# dotenv
#
.env:
	@cp .env.example .env
	@echo "HOST_USER_UID=$(shell id -u)" >> .env
	@echo "HOST_USER_GID=$(shell id -g)" >> .env
	@read -p "Enter project name (only lowercase characters): " project_name; \
		sed -i -e "s/COMPOSE_PROJECT_NAME=/COMPOSE_PROJECT_NAME=$${project_name}/g" .env

#
# Composer
#
auth.json:
	@cp auth.json.example auth.json

vendor: composer.json
	@$(MAKE) composer-install

.PHONY: composer-install
composer-install: auth.json
	@./scripts/shell.sh composer install

.PHONY: vendor-clean
vendor-clean:
	@-rm -rf ./vendor/

#
# Docker
#
.PHONY: migrations
migrations:
	@docker-compose up migrations-dev

.PHONY: up
up:
	@docker-compose --profile dev-environment up -d

.PHONY: up-release-test
up-release-test:
	@docker-compose --profile release-test-environment up -d

.PHONY: down
down:
	@docker-compose --profile dev-environment --profile release-test-environment --profile tools down

.PHONY: optional-down
optional-down:
	@-docker-compose --profile dev-environment --profile release-test-environment --profile tools --log-level critical down

.PHONY: docker-init
docker-init: var/database/mariadb var/cache build-images config-files

.PHONY: docker-clean
docker-clean: optional-down remove-config-files remove-containers remove-images remove-cache-directory remove-mariadb-directory

.PHONY: build-images
build-images: build-php-image build-mariadb-image

.PHONY: build-php-image
build-php-image:
	@docker build \
		--progress=plain \
        --pull \
		--build-arg PHP_VERSION=${php_version} \
		--build-arg COMPOSER_VERSION=${composer_version} \
		--build-arg XDEBUG_VERSION=${xdebug_version} \
		--build-arg YACRON_VERSION=${yacron_version} \
		-t "${COMPOSE_PROJECT_NAME}-php" \
		-f "./docker/php/apache/Dockerfile" \
		--target development \
		./

.PHONY: build-mariadb-image
build-mariadb-image:
	@docker build \
		--progress=plain \
        --pull \
		--build-arg MARIADB_VERSION=${mariadb_version} \
		-t "${COMPOSE_PROJECT_NAME}-mariadb" \
		./docker/database/mariadb/

.PHONY: remove-containers
remove-containers:
	@./scripts/remove-containers.sh "${COMPOSE_PROJECT_NAME}_*"

.PHONY: remove-images
remove-images:
	@./scripts/remove-images.sh "${COMPOSE_PROJECT_NAME}-*"

var/database/mariadb:
	@mkdir -p ./var/database/mariadb/

.PHONY: remove-mariadb-directory
remove-mariadb-directory:
	@-rm -rf ./var/database/mariadb/

var/cache:
	@mkdir -p ./var/cache/

.PHONY: remove-cache-directory
remove-cache-directory:
	@-rm -rf ./var/cache/

.PHONY: config-files
config-files:
	@mkdir -p ./var/generated-configs/
	@touch ./var/generated-configs/mariadb-dev.env
	@touch ./var/generated-configs/mariadb-test.env
	@touch ./var/generated-configs/application.env
	@touch ./var/generated-configs/xdebug.ini
	@touch ./var/mariadb-dev.override.env
	@touch ./var/mariadb-test.override.env
	@touch ./var/application.override.env
	@./scripts/shell.sh scripts/generate-config-files.sh

.PHONY: remove-config-files
remove-config-files:
	@-rm -f ./var/application.override.env
	@-rm -f ./var/mariadb-test.override.env
	@-rm -f ./var/mariadb-dev.override.env
	@-rm -rf ./var/generated-configs/
	@-rm -f ./xdebug.ini # legacy file, is now generated in ./var/generated-configs/

#
# Project
#
.PHONY: project-init
project-init:
	@mkdir -p ./var/log/

.PHONY: project-clean
project-clean:
	@-rm -rf ./*.build
	@-rm -rf ./var/log/

.PHONY: new-migration
new-migration:
	@./scripts/doctrine.sh --use-test-database migrations:diff
