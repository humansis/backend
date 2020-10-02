#!/usr/bin/env bash

echo "** Dropping database **"
php bin/console doctrine:database:drop --force
rm -rf var/cache/*

echo "** Creating database **"
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console cache:clear
php bin/console ra:cacheimport:clear
php bin/console reporting:code-indicator:add
echo "** Database created **"
