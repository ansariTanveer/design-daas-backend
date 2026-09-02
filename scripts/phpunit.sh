#!/usr/bin/env sh

set -e

exec ./scripts/tools.sh vendor/bin/phpunit "$@"
