#!/usr/bin/env sh

set -e

if test -z "$1"; then
    echo "Missing first argument (FILTER)" >&2
    exit 1
fi

CONTAINERS=`docker container ls -q -a --filter=name="${1}"`
if test -n "$CONTAINERS"; then
    for CONTAINER_ID in $CONTAINERS; do
        echo "Removing container $CONTAINER_ID ..."
        docker container rm -v "$CONTAINER_ID"
    done
fi
