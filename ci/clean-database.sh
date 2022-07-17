#!/usr/bin/env bash

# parameters:
# $1: run migrations (migrations)

echo "** Dropping database **"
php bin/console doctrine:database:drop --force
rm -rf var/cache/*

echo "** Creating database **"
php bin/console doctrine:database:create
if [[ $1 == "migrations" ]]; then
  php bin/console doctrine:migrations:migrate -n
  php bin/console cache:clear
  php bin/console ra:cacheimport:clear
fi
echo "** Database created **"
