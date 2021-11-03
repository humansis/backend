#!/usr/bin/env bash

# parameters:
# $1: environment (dev, test, stage, demo, prod)
# $2: clean database (true, false, database)
# $3: load fixtures (dev, test, false)
# $4: cache clear mode (normal, aggressive)

# immediately exits if a command exits with an non-zero status
set -e

# configure
echo "Configuring application build"
ec2_user="ec2-user"
if [[ $1 == "prod" ]]; then
  EC2_ASG=prod-asg
  JWT_CERT=$JWT_CERT_PROD
  JWT_KEY=$JWT_KEY_PROD
  mv docker/docker-compose.yml.prod docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_PROD} ${RDS_DB_NAME_PROD} ${RDS_USERNAME_PROD} ${RDS_PASSWORD_PROD} ${MOBILE_KEY_PROD} ${MOBILE_APP_VERSION_PROD} ${MOBILE_APP_ID_PROD} ${JWT_PASSPHRASE_PROD} ${GELF_SERVER_NAME_PROD}
elif [[ $1 == "demo" ]]; then
  EC2_ASG=demo-asg
  JWT_CERT=$JWT_CERT_DEMO
  JWT_KEY=$JWT_KEY_DEMO
  mv docker/docker-compose.yml.demo docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DEMO} ${RDS_DB_NAME_DEMO} ${RDS_USERNAME_DEMO} ${RDS_PASSWORD_DEMO} ${MOBILE_KEY_DEMO} ${MOBILE_APP_VERSION_DEMO} ${MOBILE_APP_ID_DEMO} ${JWT_PASSPHRASE_DEMO} ${GELF_SERVER_NAME_DEMO}
elif [[ $1 == "stage" ]]; then
  EC2_ASG=stage-asg
  JWT_CERT=$JWT_CERT_STAGE
  JWT_KEY=$JWT_KEY_STAGE
  mv docker/docker-compose.yml.stage docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_STAGE} ${RDS_DB_NAME_STAGE} ${RDS_USERNAME_STAGE} ${RDS_PASSWORD_STAGE} ${MOBILE_KEY_STAGE} ${MOBILE_APP_VERSION_STAGE} ${MOBILE_APP_ID_STAGE} ${JWT_PASSPHRASE_STAGE} ${GELF_SERVER_NAME_STAGE}
elif [[ $1 == "test" ]]; then
  EC2_ASG=test-asg
  JWT_CERT=$JWT_CERT_TEST
  JWT_KEY=$JWT_KEY_TEST
  mv docker/docker-compose.yml.test-new docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_TEST} ${RDS_DB_NAME_TEST} ${RDS_USERNAME_TEST} ${RDS_PASSWORD_TEST} ${MOBILE_KEY_TEST} ${MOBILE_APP_VERSION_TEST} ${MOBILE_APP_ID_TEST} ${JWT_PASSPHRASE_TEST} ${GELF_SERVER_NAME_TEST}
elif [[ $1 == "dev" ]]; then
  EC2_ASG=dev-asg
  JWT_CERT=$JWT_CERT_DEV
  JWT_KEY=$JWT_KEY_DEV
  mv docker/docker-compose.yml.dev docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DEV} ${RDS_DB_NAME_DEV} ${RDS_USERNAME_DEV} ${RDS_PASSWORD_DEV} ${MOBILE_KEY_DEV} ${MOBILE_APP_VERSION_DEV} ${MOBILE_APP_ID_DEV} ${JWT_PASSPHRASE_DEV} ${GELF_SERVER_NAME_DEV}
elif [[ $1 == "proddca" ]]; then # DCA
  echo "Not supported environment"
  exit 1
  ec2_user="admin"
  ec2_host="api.dca.humansis.org"
  JWT_CERT=$JWT_CERT_PRODDCA
  JWT_KEY=$JWT_KEY_PRODDCA
  mv docker/docker-compose.yml.template docker-compose.yml
  sed -i -e "s|%env(EC2_HOSTNAME)%|${ec2_host}|g" docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DCA_PROD} ${RDS_DB_NAME_DCA_PROD} ${RDS_USERNAME_DCA_PROD} ${RDS_PASSWORD_DCA_PROD} ${MOBILE_KEY_DCA_PROD} ${MOBILE_APP_VERSION_DCA_PROD} ${MOBILE_APP_ID_DCA_PROD} ${JWT_PASSPHRASE_PRODDCA} ${GELF_SERVER_NAME_PRODDCA}
elif [[ $1 == "testdca" ]]; then # DCA
  echo "Not supported environment"
  exit 1
  ec2_user="admin"
  ec2_host="api.testdca.humansis.org"
  JWT_CERT=$JWT_CERT_TESTDCA
  JWT_KEY=$JWT_KEY_TESTDCA
  mv docker/docker-compose.yml.template docker-compose.yml
  sed -i -e "s|%env(EC2_HOSTNAME)%|${ec2_host}|g" docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DCA_TEST} ${RDS_DB_NAME_DCA_TEST} ${RDS_USERNAME_DCA_TEST} ${RDS_PASSWORD_DCA_TEST} ${MOBILE_KEY_DCA_TEST} ${MOBILE_APP_VERSION_DCA_TEST} ${MOBILE_APP_ID_DCA_TEST} ${JWT_PASSPHRASE_TESTDCA} ${GELF_SERVER_NAME_TESTDCA}
else
  echo "Wrong environment parameter. Options are: [dev, test, stage, demo, prod, testdca, proddca]"
  exit 1
fi
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
scp docker-compose.yml app/config/parameters.yml $ec2_user@$ec2_host:/opt/humansis
rsync --chmod=u+rw,g-rwx,o-rwx $JWT_KEY $ec2_user@$ec2_host:/opt/humansis/jwt/private.pem
rsync --chmod=u+rw,g+rw,o+r $JWT_CERT $ec2_user@$ec2_host:/opt/humansis/jwt/public.pem
start_app="cd /opt/humansis && sudo docker-compose pull && sudo docker-compose up -d"
ssh $ec2_user@$ec2_host $start_app

# clean database
echo "Cleaning database"
if [[ $2 == "true" ]]; then
  clean_database="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'bash clean_database.sh migrations'"
  ssh $ec2_user@$ec2_host $clean_database
elif [[ $2 == "database" ]]; then
  clean_database="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'bash clean_database.sh'"
  ssh $ec2_user@$ec2_host $clean_database
  # get database
  bash get_db.sh ${RDS_HOSTNAME_STAGE} ${DB_DEPLOY_USER} ${DB_DEPLOY_USER_PASSWORD} ${DB_DEPLOY_NAME} "$1"
  # run database migrations
  migrations="cd /opt/humansis && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'"
  ssh $ec2_user@$ec2_host $migrations
elif [[ $2 == "false" ]]; then
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
scp clear_cache.sh $ec2_user@$ec2_host:/opt/humansis
cache_clear="cd /opt/humansis && bash ./clear_cache.sh $4"
ssh $ec2_user@$ec2_host "$cache_clear" || exit 1
echo "...done"
