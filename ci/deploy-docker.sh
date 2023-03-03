#!/usr/bin/env bash

# parameters:
# $1: environment (dev1, test[1:3], stage, demo, production)
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
  CONSUMER_EC2_ASG=consumer-prod-asg
  mv docker/docker-compose.prod.yml docker-compose.yml
  mv docker/docker-compose.prod-consumer.yml docker-compose.consumer.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|production|${CI_COMMIT_TAG}|g" docker-compose.yml
  sed -i -e "s|production|${CI_COMMIT_TAG}|g" docker-compose.consumer.yml
elif [[ $1 == "demo" ]]; then
  EC2_ASG=demo-asg
  mv docker/docker-compose.demo.yml docker-compose.yml
elif [[ $1 == "stage" ]]; then
  EC2_ASG=stage-asg
  CONSUMER_EC2_ASG=consumer-stage-asg
  mv docker/docker-compose.stage.yml docker-compose.yml
  mv docker/docker-compose.stage-consumer.yml docker-compose.consumer.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|__STAGE__|stage|g" docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.consumer.yml
  sed -i -e "s|__STAGE__|stage|g" docker-compose.consumer.yml
elif [[ $1 == "stage2" ]]; then
  EC2_ASG=stage2-asg
  CONSUMER_EC2_ASG=consumer-stage2-asg
  mv docker/docker-compose.stage.yml docker-compose.yml
  mv docker/docker-compose.stage-consumer.yml docker-compose.consumer.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|__STAGE__|stage2|g" docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.consumer.yml
  sed -i -e "s|__STAGE__|stage2|g" docker-compose.consumer.yml
elif [[ $1 == "test" ]]; then
  EC2_ASG=test-asg
  mv docker/docker-compose.test.yml docker-compose.yml
elif [[ $1 == "dev1" ]]; then
  EC2_ASG=dev-asg
  mv docker/docker-compose.dev.yml docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|__DEV__|dev1|g" docker-compose.yml
elif [[ $1 == "test2" ]]; then
  EC2_ASG=test2-asg
  mv docker/docker-compose.test.yml docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|__TEST__|test2|g" docker-compose.yml
elif [[ $1 == "test3" ]]; then
  EC2_ASG=test3-asg
  mv docker/docker-compose.test.yml docker-compose.yml
  # CAREFUL: replaces tokens in docker-compose.yml
  sed -i -e "s|__TEST__|test3|g" docker-compose.yml
else
  echo "Wrong environment parameter. Options are: [dev1, test, test2, test3, stage, demo, production]"
  exit 1
fi

# CAREFUL: replaces tokens in docker-compose.yml
./ci/apply-env-config.sh ${RDS_HOSTNAME} ${RDS_DB_NAME} ${RDS_USERNAME} ${RDS_PASSWORD} ${MOBILE_KEY} ${MOBILE_APP_VERSION} ${MOBILE_APP_ID} ${JWT_PASSPHRASE} ${GELF_SERVER_NAME}

echo "...done"

echo "scale down running instances"
while [ $(aws autoscaling describe-auto-scaling-groups --auto-scaling-group-name ${EC2_ASG} --query 'length(AutoScalingGroups[*].Instances[?LifecycleState==`InService`][])') -ne 1 ] ; do
  aws autoscaling set-desired-capacity --auto-scaling-group-name ${EC2_ASG} --desired-capacity 1
  echo "waiting for scale down, sleep for 20s"
  if [[ -f docker-compose.consumer.yml ]]; then
    # turn off consumer instance before deployment
    aws autoscaling set-desired-capacity --auto-scaling-group-name ${CONSUMER_EC2_ASG} --desired-capacity 0
  fi
  sleep 20;
done
INSTANCE_ID=$(aws autoscaling describe-auto-scaling-groups --auto-scaling-group-name ${EC2_ASG} --output text --query 'AutoScalingGroups[*].Instances[?LifecycleState==`InService`].InstanceId')
ec2_host=$(aws ec2 describe-instances --instance-ids ${INSTANCE_ID} --output text --query 'Reservations[*].Instances[*].PublicIpAddress')
echo "...done"

# safely wait for the consumer instance to be turned off
if [[ -f docker-compose.consumer.yml ]]; then
  while [ $(aws autoscaling describe-auto-scaling-groups --auto-scaling-group-name ${CONSUMER_EC2_ASG} --query 'length(AutoScalingGroups[*].Instances[?LifecycleState==`InService`][])') -ne 0 ] ; do
    echo "waiting for scale down, sleep for 20s"
    # turn off consumer instance before deployment
    aws autoscaling set-desired-capacity --auto-scaling-group-name ${CONSUMER_EC2_ASG} --desired-capacity 0
    sleep 20;
  done
fi
# add host to known_hosts
if [[ -z `ssh-keygen -F $ec2_host` ]]; then
  ssh-keyscan -H $ec2_host >> ~/.ssh/known_hosts
fi

echo "Starting application containers"
scp docker-compose.yml app/config/parameters.yml ci/cron.sh ci/cron-recalculate-spent.sh $ec2_user@$ec2_host:/opt/humansis
if [[ -f docker-compose.consumer.yml ]]; then
  scp docker-compose.consumer.yml $ec2_user@$ec2_host:/opt/humansis
fi
rsync --chmod=u+rw,g-rwx,o-rwx $JWT_KEY $ec2_user@$ec2_host:/opt/humansis/jwt/private.pem
rsync --chmod=u+rw,g+rw,o+r $JWT_CERT $ec2_user@$ec2_host:/opt/humansis/jwt/public.pem
start_app="cd /opt/humansis && sudo docker-compose pull && sudo docker-compose up -d"
ssh $ec2_user@$ec2_host $start_app

if [[ ! -f docker-compose.consumer.yml ]]; then
  stop_consumer="cd /opt/humansis && sudo docker-compose stop consumer"
  ssh $ec2_user@$ec2_host $stop_consumer
fi

# clear cache
# normal: php bin/console cache:clear --env=prod + php bin/console cache:clear
# aggressive: normal + rm ./var/cache/* + docker restart php_container
echo "Clearing cache"
scp ./ci/clear-cache.sh $ec2_user@$ec2_host:/opt/humansis
cache_clear="cd /opt/humansis && bash ./clear-cache.sh $4"
ssh $ec2_user@$ec2_host "$cache_clear" || exit 1
echo "...done"

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

echo "Downloading crowdin translations"
crowdin_pull="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console crowdin:pull'"
ssh $ec2_user@$ec2_host "$crowdin_pull" || exit 1
crowdin_cache="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console cache:clear'"
ssh $ec2_user@$ec2_host "$crowdin_cache" || exit 1
echo "...done"

# create default admin user
if [[ $1 == "stage" ]] || [[ $1 == "stage2" ]] ; then
  admin_user="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console app:default-credentials'"
  ssh $ec2_user@$ec2_host $admin_user
fi

if [[ -f docker-compose.consumer.yml ]]; then
  # turn on consumer instance before deployment
  aws autoscaling set-desired-capacity --auto-scaling-group-name ${CONSUMER_EC2_ASG} --desired-capacity 1
else
  start_consumer="cd /opt/humansis && sudo docker-compose start consumer"
  ssh $ec2_user@$ec2_host $start_consumer
fi

rm_old_images="sudo docker system prune -f"
ssh $ec2_user@$ec2_host $rm_old_images
