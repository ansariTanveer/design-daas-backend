#!/usr/bin/env bash

set -e

./scripts/generate-mariadb-dev-env.sh > ./var/generated-configs/mariadb-dev.env
./scripts/generate-mariadb-test-env.sh > ./var/generated-configs/mariadb-test.env
./scripts/generate-application-env.sh > ./var/generated-configs/application.env
./scripts/generate-xdebug-ini.sh > ./var/generated-configs/xdebug.ini
