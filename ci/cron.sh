#!/bin/bash
cd /opt/humansis
docker-compose exec -T php bash -c 'php bin/console app:import:finish' || true

docker-compose exec -T php bash -c 'php bin/console app:import:load' || true
docker-compose exec -T php bash -c 'php bin/console app:import:integrity' || true
docker-compose exec -T php bash -c 'php bin/console app:import:identity' || true
docker-compose exec -T php bash -c 'php bin/console app:import:similarity' || true

docker-compose exec -T php bash -c 'php bin/console app:import:clean' || true
