#!/bin/sh

# Rabbitmq
# apt-key adv --keyserver keyserver.ubuntu.com --recv-keys 6B73A36E6026DFCA

# apt-get update

export PHP_VERSION=${PHP_VERSION:-7.1}
export PLUGIN_SLUG=${PLUGIN_SLUG:-noname}
export DB_NAME=${DB_NAME:-wordpress_tests}

# MySQL start
service mysql start

# Display versions
echo '============================================='
echo WordPress version: $WP_VERSION
echo PHP version: $PHP_VERSION
echo MySQL version: `mysql --version|awk '{ print $5 }'|awk -F\, '{ print $1 }'`
echo PHPUnit version: `php-${PHP_VERSION} /opt/phpunit-php-${PHP_VERSION} --version|awk '{ print $2 }'|awk -F\, '{ print $1 }'`
echo Codeception version: `php-${PHP_VERSION} /opt/codecept-php-${PHP_VERSION} --version|awk '{ print $2 $3 }'|awk -F\, '{ print $1 }'`

echo Plugin slug: $PLUGIN_SLUG
echo '============================================='
echo ''

cd /plugin

# Before script
echo "Copying plugin files..."
cp -rf src/ /var/www/src/wp-content/plugins/$PLUGIN_SLUG
ln -s /var/www /tmp/wordpress

echo "Creating a database..."
mysql -e "DROP DATABASE IF EXISTS ${DB_NAME};" -uroot
mysql -e "CREATE DATABASE ${DB_NAME};" -uroot

echo "Configuring WordPress..."
cp /var/www/wp-tests-config-sample.php /var/www/wp-tests-config.php
sed -i "s/youremptytestdbnamehere/${DB_NAME}/" /var/www/wp-tests-config.php
sed -i "s/yourusernamehere/root/" /var/www/wp-tests-config.php
sed -i "s/yourpasswordhere//" /var/www/wp-tests-config.php

# Script
echo "Running the tests"
php-${PHP_VERSION} /opt/phpunit-php-${PHP_VERSION} $@
