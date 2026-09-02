#!/usr/bin/env sh

set -e

exec docker-compose run --rm tools "$@"
