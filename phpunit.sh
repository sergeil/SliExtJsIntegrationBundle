#!/usr/bin/env bash

set -eu

PHP_VERSION=7.1

if ! type docker > /dev/null; then
    echo "Docker is required to run tests."
    exit 1
fi

MYSQL_DB_NAME=sergeil_extjsintegration
MYSQL_DB_PASSWORD=123123

if [[ `docker ps` != *"${MYSQL_DB_NAME}"* ]]; then
    echo "# Starting database for functional tests"
    docker run -d \
        -e MYSQL_DATABASE=${MYSQL_DB_NAME} \
        -e MYSQL_ROOT_PASSWORD=${MYSQL_DB_PASSWORD} \
        --name ${MYSQL_DB_NAME} \
        mysql:5 > /dev/null
else
    echo "# MySQL container is already running, reusing it"
fi

MYSQL_DB_HOST=$(docker inspect ${MYSQL_DB_NAME} --format '{{.NetworkSettings.IPAddress}}')

while ! echo exit | nc -z ${MYSQL_DB_HOST} 3306; do
    echo ".";
    sleep 3;
done

if [ ! -d "vendor" ]; then
    echo "# No vendor dir detected, installing dependencies first then"
    docker run \
        -it \
        --rm \
        -w /mnt/tmp \
        -v `pwd`:/mnt/tmp \
        -e DEBIAN_FRONTEND=noninteractive \
        -e COMPOSER_MEMORY_LIMIT=-1 \
        -e COMPOSER_INSTALLER=https://getcomposer.org/installer \
        php:${PHP_VERSION} sh -c '\
            apt-get update && \
            apt-get install -yq \
                git \
                unzip \
            && \
            curl -sS ${COMPOSER_INSTALLER} | php -- --quiet --install-dir=/usr/local/bin --filename=composer && \
            composer install \
        '
fi

echo ""

docker run \
    -it \
    --rm \
    -v `pwd`:/mnt/tmp \
    -w /mnt/tmp \
    -e SYMFONY__DB_HOST=${MYSQL_DB_HOST} \
    -e SYMFONY__DB_PORT=3306 \
    -e SYMFONY__DB_USER=root \
    -e SYMFONY__DB_PASSWORD=${MYSQL_DB_PASSWORD} \
    php:${PHP_VERSION} sh -c '\
        apt-get update && \
        apt-get install -yq \
            libfreetype6-dev \
            libjpeg62-turbo-dev \
            libpng-dev \
        && \
        docker-php-ext-configure \
            pdo_mysql --with-pdo-mysql=mysqlnd \
        && \
        docker-php-ext-install -j$(nproc) \
            pdo_mysql \
        && \
        vendor/bin/phpunit \
    '

exit_code=$?

docker rm -fv ${MYSQL_DB_NAME} > /dev/null

exit $exit_code