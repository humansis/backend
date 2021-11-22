#!/usr/bin/env bash

composer upgrade humansis/user-app-api humansis/user-app-legacy-api humansis/vendor-app-api humansis/vendor-app-legacy-api humansis/web-api
composer install

echo "** Dropping database **"
php bin/console d:d:d --force
rm -rf var/cache/*
echo "** Creating database **"
php bin/console d:d:c
php bin/console d:m:m -n
php bin/console d:f:l -n
php bin/console r:c:c
php bin/console reporting:code-indicator:add
rm -rf var/cache/*
php bin/console cache:clear

echo "** Starting cron **"
status=$(printf 'symfony\n' | sudo -S service cron status | cut -c 1-16)
if [[ "$status" != "cron is running." ]]; then
    printf 'symfony\n' | sudo -S cron
fi
