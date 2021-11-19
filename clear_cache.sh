#!/usr/bin/env bash

# parameters:
# $1: clear mode (normal, aggressive)
sudo docker-compose exec -T php bash -c 'php bin/console cache:clear'
sudo docker-compose exec -T php bash -c 'php bin/console cache:clear --env=prod'

if [[ $1 == "aggressive" ]]; then
  rm -rf var/cache/*
  sudo docker-compose restart php
fi
