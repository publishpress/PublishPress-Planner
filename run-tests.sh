#!/bin/bash

docker run -it --rm -v $PWD:/plugin -e PHP_VERSION=${1:-7.1} -e PLUGIN_SLUG=publishpress ostraining/phpfarm-wordpress-tests:${2:-4.7} /plugin/tools/local.tests.sh
