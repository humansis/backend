#!/usr/bin/env bash

echo "** Dropping database **"
php bin/console d:d:d --force
rm -rf var/cache/*
echo "** Creating database **"
php bin/console d:d:c
php bin/console d:m:m -n
php bin/console d:f:l -n
php bin/console r:c:c
rm -rf var/cache/*
php bin/console cache:clear

echo "** Starting cron **"
status=$(printf 'symfony\n' | sudo -S service cron status | cut -c 1-16)
if [[ "$status" != "cron is running." ]]; then
    printf 'symfony\n' | sudo -S cron
fi
