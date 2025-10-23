#!/usr/bin/env bash

# This script builds a multi-platform Docker image and pushes it to Docker Hub
#
# Usage:
#   ./build-push-image.sh <image-tag> <build-context>
#
# Arguments:
#   $1 - The image tag (e.g., username/image:tag)
#   $2 - The build context directory path
#
# Example:
#   ./build-push-image.sh myusername/myapp:1.0.0 ./app
#
# Prerequisites:
#   - Docker login is required (run `docker login` before using this script)
#   - Docker buildx plugin must be installed and configured


# This command requires to be logged in on Docker Hub. Check `docker login --help` for more information.
docker buildx build --platform linux/amd64 --push -t $1 $2
