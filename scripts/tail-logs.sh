#!/usr/bin/env sh

set -e

exec docker-compose logs -f "$@"
