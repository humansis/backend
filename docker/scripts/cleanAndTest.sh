#!/usr/bin/env bash

clean
bin/console app:adm:upload --limit=10 --all

vendor/bin/phpunit
