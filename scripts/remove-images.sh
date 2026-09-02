#!/usr/bin/env sh

set -e

if test -z "$1"; then
    echo "Missing first argument (FILTER)" >&2
    exit 1
fi

IMAGES=`docker images --filter=reference="${1}" --format="{{.Repository}}:{{.Tag}}"`
if test -n "$IMAGES"; then
    for IMAGE_NAME in $IMAGES; do
        echo "Removing image $IMAGE_NAME ..."
        docker image rm "$IMAGE_NAME"
    done
fi
