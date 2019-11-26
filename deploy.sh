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
elif [[ $1 == "dev" || $1 == "dev-dial" ]]; then
    ec2_test="ec2-52-57-90-156.eu-central-1.compute.amazonaws.com"
    if [ -z `ssh-keygen -F $ec2_test` ]; then
        ssh-keyscan -H $ec2_test >> ~/.ssh/known_hosts
    fi
else
    echo "Unknown environment"
    exit
fi

command="cd /var/www/html/bms_api; \
    git checkout $1; \
    git pull origin $1; \
    sudo docker-compose exec -T php bash -c 'composer install';\
    sudo docker-compose exec -T php bash -c 'php bin/console c:c'; \
    sudo docker-compose exec  -T php bash -c 'php bin/console d:m:m -n'"

if [[ $1 == "master" ]]; then
    ssh -i $2 ubuntu@$ec2_prod $command
    ssh -i $2 ubuntu@$ec2_demo $command
elif [[ $1 == "dev" || $1 == "dev-dial" ]]; then
    ssh -i $2 ubuntu@$ec2_test $command
fi