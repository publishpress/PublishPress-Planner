#!/usr/bin/env bash

# If not in the `dev-workspace` directory, change to it
if [[ ! $(pwd) =~ .*dev-workspace$ ]]; then
  cd dev-workspace
fi

set -a
source ../.env
set +a

PROFILE=$1

TARGET_CONTAINER_NAME=${CONTAINER_NAME}-env-${PROFILE}-wp
echo "Logging to $TARGET_CONTAINER_NAME"

# Get the logs of the web container
docker logs -f $TARGET_CONTAINER_NAME
