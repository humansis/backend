#!/usr/bin/env bash

clean
bin/console app:adm:update --limit=10 --all

php -d memory_limit=-1 vendor/bin/phpunit
