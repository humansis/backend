#!/usr/bin/env bash

# parameters:
# $1: environment (dev[1:3], test, stage, demo, production)
# $2: clean database (true, false, database)
# $3: load fixtures (dev, test, false)
# $4: cache clear mode (normal, aggressive)

# immediately exits if a command exits with an non-zero status
set -e

# configure
echo "Configuring application build"
export ec2_user="ec2-user"
if [[ $1 == "production" ]]; then
  EC2_ASG=prod-asg
  mv docker/docker-compose.prod.yml docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|production|${CI_COMMIT_TAG}|g" docker-compose.yml
elif [[ $1 == "demo" ]]; then
  EC2_ASG=demo-asg
  mv docker/docker-compose.demo.yml docker-compose.yml
elif [[ $1 == "stage" ]]; then
  EC2_ASG=stage-asg
  mv docker/docker-compose.stage.yml docker-compose.yml
elif [[ $1 == "test" ]]; then
  EC2_ASG=test-asg
  mv docker/docker-compose.test.yml docker-compose.yml
elif [[ $1 == "dev1" ]]; then
  EC2_ASG=dev-asg
  mv docker/docker-compose.dev.yml docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|__DEV__|dev1|g" docker-compose.yml
elif [[ $1 == "dev2" ]]; then
  EC2_ASG=dev2-asg
  mv docker/docker-compose.dev.yml docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|__DEV__|dev2|g" docker-compose.yml
elif [[ $1 == "dev3" ]]; then
  EC2_ASG=dev3-asg
  mv docker/docker-compose.dev.yml docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|__DEV__|dev3|g" docker-compose.yml
else
  echo "Wrong environment parameter. Options are: [dev1, dev2, dev3, test, stage, demo, production]"
  exit 1
fi

# CAREFUL: replaces tokens in docker-compose.yml
./ci/apply-env-config.sh ${RDS_HOSTNAME} ${RDS_DB_NAME} ${RDS_USERNAME} ${RDS_PASSWORD} ${MOBILE_KEY} ${MOBILE_APP_VERSION} ${MOBILE_APP_ID} ${JWT_PASSPHRASE} ${GELF_SERVER_NAME}

echo "...done"

while [ $(aws autoscaling describe-auto-scaling-groups --auto-scaling-group-name ${EC2_ASG} --query 'length(AutoScalingGroups[*].Instances[?LifecycleState==`InService`][])') -gt 1 ] ; do
  aws autoscaling set-desired-capacity --auto-scaling-group-name ${EC2_ASG} --desired-capacity 1
  echo "waiting for scale down, sleep for 20s"
  sleep 20;
done
INSTANCE_ID=$(aws autoscaling describe-auto-scaling-groups --auto-scaling-group-name ${EC2_ASG} --output text --query 'AutoScalingGroups[*].Instances[?LifecycleState==`InService`].InstanceId')
ec2_host=$(aws ec2 describe-instances --instance-ids ${INSTANCE_ID} --output text --query 'Reservations[*].Instances[*].PublicIpAddress')

# add host to known_hosts
if [[ -z `ssh-keygen -F $ec2_host` ]]; then
  ssh-keyscan -H $ec2_host >> ~/.ssh/known_hosts
fi

echo "Starting application containers"
scp docker-compose.yml app/config/parameters.yml ci/cron.sh $ec2_user@$ec2_host:/opt/humansis
rsync --chmod=u+rw,g-rwx,o-rwx $JWT_KEY $ec2_user@$ec2_host:/opt/humansis/jwt/private.pem
rsync --chmod=u+rw,g+rw,o+r $JWT_CERT $ec2_user@$ec2_host:/opt/humansis/jwt/public.pem
start_app="cd /opt/humansis && sudo docker-compose pull && sudo docker-compose up -d"
ssh $ec2_user@$ec2_host $start_app

# clean database
if [[ $2 == "true" ]]; then
  echo "Cleaning database"
  clean_database="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'bash clean-database.sh migrations'"
  ssh $ec2_user@$ec2_host $clean_database
elif [[ $2 == "database" ]]; then
  echo "Cleaning database - copying ${DB_DEPLOY_NAME} database"
  clean_database="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'bash clean-database.sh'"
  ssh $ec2_user@$ec2_host $clean_database
  # get database
  ./ci/get-db.sh ${RDS_HOSTNAME} ${DB_DEPLOY_USER} ${DB_DEPLOY_USER_PASSWORD} ${DB_DEPLOY_NAME} "$1"
  # run database migrations
  migrations="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'"
  ssh $ec2_user@$ec2_host $migrations
elif [[ $2 == "false" ]]; then
  echo "Running migrations only, keeping database intact"
  # run database migrations
  migrations="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'"
  ssh $ec2_user@$ec2_host $migrations
else
  echo "Wrong clean database parameter. Options are: [true, false, database]"
  exit 1
fi
echo "...done"

# load fixtures
echo "Loading fixtures"
if [[ $3 != "false" ]]; then
  if [[ $3 == "dev" ]]; then
    load_fixtures="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:fixtures:load -n --env=dev'"
  elif [[ $3 == "test" ]]; then
    load_fixtures="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:fixtures:load -n --env=test'"
  else
    echo "Wrong fixtures environment parameter. Options are: [dev, test, false (do not load fixtures)]"
    exit 1
  fi
  if [[ ! -z $load_fixtures ]]; then
    ssh $ec2_user@$ec2_host $load_fixtures
  fi
fi
echo "...done"

# clear cache
# normal: php bin/console cache:clear --env=prod + php bin/console cache:clear
# aggressive: normal + rm ./var/cache/* + docker restart php_container
echo "Clearing cache"
scp ./ci/clear-cache.sh $ec2_user@$ec2_host:/opt/humansis
cache_clear="cd /opt/humansis && bash ./clear-cache.sh $4"
ssh $ec2_user@$ec2_host "$cache_clear" || exit 1
echo "...done"

rm_old_images="sudo docker system prune -f"
ssh $ec2_user@$ec2_host $rm_old_images
