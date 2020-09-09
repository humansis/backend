#!/bin/bash

#immediately exits if a command exits with an non-zero status
set -e

if [[ $1 == "master" ]]; then
    ec2_prod="ec2-35-158-182-63.eu-central-1.compute.amazonaws.com"
    ec2_demo="ec2-18-185-233-146.eu-central-1.compute.amazonaws.com"
    if [ -z `ssh-keygen -F $ec2_prod` ]; then
        ssh-keyscan -H $ec2_prod >> ~/.ssh/known_hosts
    fi
    if [ -z `ssh-keygen -F $ec2_demo` ]; then
        ssh-keyscan -H $ec2_demo >> ~/.ssh/known_hosts
    fi
elif [[ $1 == "dev" ]]; then
    ec2_test="ec2-35-157-77-79.eu-central-1.compute.amazonaws.com"
    if [ -z `ssh-keygen -F $ec2_test` ]; then
        ssh-keyscan -H $ec2_test >> ~/.ssh/known_hosts
    fi
elif [[ $1 =~ ^release\/.*$ ]]; then
    ec2_stage="ec2-18-156-21-101.eu-central-1.compute.amazonaws.com"
    if [ -z `ssh-keygen -F $ec2_stage` ]; then
        ssh-keyscan -H $ec2_stage >> ~/.ssh/known_hosts
    fi
else
    ec2_dev="ec2-52-57-90-156.eu-central-1.compute.amazonaws.com"
    if [ -z `ssh-keygen -F $ec2_dev` ]; then
        ssh-keyscan -H $ec2_dev >> ~/.ssh/known_hosts
    fi
fi

command="cd /var/www/html/bms_api; \
    git pull origin-bis $1; \
    git checkout $1; \
    ./hooks/post-checkout; \
    sudo docker-compose exec -T php bash -c 'composer install';\
    sudo docker-compose exec -T php bash -c 'php bin/console cache:clear --env=prod'; \
    sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'"


command_stage="cd /var/www/html/bms_api; \
    git pull origin-bis $1; \
    git checkout $1; \
    ./hooks/post-checkout; \
    sudo docker-compose exec -T php bash -c 'composer install';\
    sudo docker-compose exec -T php bash -c 'php bin/console cache:clear --env=dev'; \
    sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'"


command_clean_db="cd /var/www/html/bms_api; \
    git pull origin-bis $1; \
    git checkout $1; \
    ./hooks/post-checkout; \
    sudo docker-compose exec -T php bash -c 'composer install'; \
    sudo docker-compose exec -T php bash -c 'php bin/console doctrine:database:drop --force'; \
    sudo docker-compose exec -T php bash -c 'rm -rf var/cache/*'; \
    sudo docker-compose exec -T php bash -c 'php bin/console doctrine:database:create'; \
    sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'; \
    sudo docker-compose exec -T php bash -c 'php bin/console cache:clear'; \
    sudo docker-compose exec -T php bash -c 'php bin/console ra:cacheimport:clear'; \
    sudo docker-compose exec -T php bash -c 'php bin/console reporting:code-indicator:add'"

fixtures_test="cd /var/www/html/bms_api; \
    sudo docker-compose exec  -T php bash -c 'php bin/console doctrine:fixtures:load --env=test';\
    sudo docker-compose exec -T php bash -c 'php bin/console cache:clear'"

fixtures_dev="cd /var/www/html/bms_api; \
    sudo docker-compose exec  -T php bash -c 'php bin/console doctrine:fixtures:load --env=dev';\
    sudo docker-compose exec -T php bash -c 'php bin/console cache:clear'"

if [[ $1 == "master" ]]; then
    ssh -i $2 ubuntu@$ec2_prod $command
    ssh -i $2 ubuntu@$ec2_demo $command
elif [[ $1 == "dev" ]]; then
    ssh -i $2 ubuntu@$ec2_test $command_clean_db
    ssh -i $2 ubuntu@$ec2_test $fixtures_dev
elif [[ $1 =~ ^release\/.*$ ]]; then
    ssh -i $2 ubuntu@$ec2_stage $command_stage
else
    ssh -i $2 ubuntu@$ec2_dev $command_clean_db
    ssh -i $2 ubuntu@$ec2_dev $fixtures_dev
fi
