#!/usr/bin/env bash

clean
bin/console app:adm:upload

vendor/bin/phpunit