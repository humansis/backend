#!/bin/bash

[[ ! -e /.dockerenv ]] && exit 0

set -xe

apt-get update && apt-get install -y \
    git \
    unzip \
    apt-transport-https \
    libpng-dev \
    zlib1g-dev \
    libicu-dev \
    g++ \
    mysql-server \
    mysql-client \
    iputils-ping

curl --location --output /usr/local/bin/phpunit https://phar.phpunit.de/phpunit.phar
chmod +x /usr/local/bin/phpunit

docker-php-ext-configure intl
docker-php-ext-install pdo pdo_mysql zip gd intl
