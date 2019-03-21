#!/usr/bin/env bash

php bin/console d:d:d --force
rm -rf var/cache/*
php bin/console d:d:c
php bin/console d:m:m -n
php bin/console d:f:l -n
php bin/console r:c:c
php bin/console jrk:levenshtein:install
php bin/console reporting:code-indicator:add
rm -rf var/cache/*
php bin/console cache:clear
status=$(printf 'symfony\n' | sudo -S service cron status)
echo $status
if [[ $status != 'cron is running.' ]]; then
    echo 'not running'
    sudo cron
fi
