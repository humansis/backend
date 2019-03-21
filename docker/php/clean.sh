#!/usr/bin/env bash

echo "** Dropping database **"
php bin/console d:d:d --force
rm -rf var/cache/*
echo "** Creating database **"
php bin/console d:d:c
php bin/console d:m:m -n
php bin/console d:f:l -n
php bin/console r:c:c
php bin/console jrk:levenshtein:install
php bin/console reporting:code-indicator:add
rm -rf var/cache/*
php bin/console cache:clear

echo "** Starting cron **"
status=$(printf 'symfony\n' | sudo -S service cron status)
if [[ $status != 'cron is running.' ]]; then
    printf 'symfony\n' | sudo -S cron
fi
