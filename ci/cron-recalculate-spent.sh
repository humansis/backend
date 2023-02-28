#!/bin/bash
cd /opt/humansis
docker-compose exec -T php bash -c 'php bin/console recalculate:spent'
