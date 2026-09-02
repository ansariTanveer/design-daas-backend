#!/usr/bin/env sh

set -e

exec ./scripts/tools.sh vendor/bin/phpcs --cache="var/cache/phpcs-result.cache" "$@"
