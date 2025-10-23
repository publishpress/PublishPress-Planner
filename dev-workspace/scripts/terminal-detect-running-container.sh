#!/usr/bin/env bash

# Check for running containers matching CONTAINER_NAME but exclude test containers
docker ps --format "{{.Names}}" | grep -q "${CONTAINER_NAME}_term"

if [ $? -ne 0 ]; then
    echo ""
else
    # Get the container name, excluding test containers
    RUNNING_CONTAINER=$(docker ps --format "{{.Names}}" | grep "${CONTAINER_NAME}_term" | head -n 1)
    echo "$RUNNING_CONTAINER"
fi
