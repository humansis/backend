#!/bin/bash

if [[ $1 == "master" ]]; then
    ec2="ec2-35-158-182-63.eu-central-1.compute.amazonaws.com"
elif [[ $1 == "dev" ]] || [[ $1 == "voucher" ]]; then
    ec2="ec2-52-57-90-156.eu-central-1.compute.amazonaws.com"
else
    echo "Unknown environment"
    exit
fi

if [ -z `ssh-keygen -F $ec2` ]; then
  ssh-keyscan -H $ec2 >> ~/.ssh/known_hosts
fi

ssh -i $2 ubuntu@$ec2 \
    "cd /var/www/html/bms_api; \
    git checkout $1; \
    git pull origin $1; \
    sudo docker-compose exec -T php bash -c 'composer install';"
if [[ $1 == "master" ]]; then
    ssh -i $2 ubuntu@$ec2 \
        "cd /var/www/html/bms_api; \
        sudo docker-compose exec -T php bash -c 'php bin/console c:c'; \
        sudo docker-compose exec  -T php bash -c 'php bin/console d:m:m -n'"
elif [[ $1 == "dev" ]] || [[ $1 == "voucher" ]]; then
    ssh -i $2 ubuntu@$ec2 \
        "cd /var/www/html/bms_api; \
        sudo docker-compose exec -T php bash -c 'clean'"
else
    echo "Unable to deploy"
    exit
fi
