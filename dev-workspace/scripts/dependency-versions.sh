#!/usr/bin/env bash

echo "PHP Version: " $(php -v)
echo "Composer Version: " $(composer --version)
echo "Node Version: " $(node -v)
echo "NPM Version: " $(npm -v)
echo "Yarn Version: " $(yarn -v)
echo "WP-CLI Version: " $(wp --version --allow-root)

if [ -f "vendor/bin/codecept" ]; then
    echo "Codeception Version: " $(vendor/bin/codecept --version)
else
    echo "Codeception Version: " "not installed"
fi

if [ -f "vendor/bin/phpcs" ]; then
    echo "PHP CodeSniffer Version: " $(vendor/bin/phpcs --version)
else
    echo "PHP CodeSniffer Version: " "not installed"
fi

if [ -f "vendor/bin/phpmd" ]; then
    echo "PHP Mess Detector Version: " $(vendor/bin/phpmd --version)
else
    echo "PHP Mess Detector Version: " "not installed"
fi

if [ -f "vendor/bin/phplint" ]; then
    echo "PHP Lint Version: " $(vendor/bin/phplint --version)
else
    echo "PHP Lint Version: " "not installed"
fi

if [ -f "vendor/bin/phpstan" ]; then
    echo "PHPStan Version: " $(vendor/bin/phpstan --version)
else
    echo "PHPStan Version: " "not installed"
fi
