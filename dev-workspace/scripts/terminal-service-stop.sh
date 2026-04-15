#!/usr/bin/env bash

RUNNING_CONTAINER=$(sh ./scripts/terminal-detect-running-container.sh)

if [ -z "$RUNNING_CONTAINER" ]; then
    echo "Container is not running"
    exit 0
fi

docker stop $RUNNING_CONTAINER
