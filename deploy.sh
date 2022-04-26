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
ec2_user="ubuntu"
if [[ $1 == "prod" ]]; then
  ec2_host="api.humansis.org"
  JWT_CERT=$JWT_CERT_PROD
  JWT_KEY=$JWT_KEY_PROD
  mv docker/docker-compose.yml.prod docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_PROD} ${RDS_DB_NAME_PROD} ${RDS_USERNAME_PROD} ${RDS_PASSWORD_PROD} ${MOBILE_KEY_PROD} ${MOBILE_APP_VERSION_PROD} ${MOBILE_APP_ID_PROD} ${JWT_PASSPHRASE_PROD} ${GELF_SERVER_NAME_PROD}
elif [[ $1 == "demo" ]]; then
  echo "Demo environment is currently not supported"
  exit 0
  ec2_host="api-demo.humansis.org"
  JWT_CERT=$JWT_CERT_DEMO
  JWT_KEY=$JWT_KEY_DEMO
  mv docker/docker-compose.yml.demo docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DEMO} ${RDS_DB_NAME_DEMO} ${RDS_USERNAME_DEMO} ${RDS_PASSWORD_DEMO} ${MOBILE_KEY_DEMO} ${MOBILE_APP_VERSION_DEMO} ${MOBILE_APP_ID_DEMO} ${JWT_PASSPHRASE_DEMO} ${GELF_SERVER_NAME_DEMO}
elif [[ $1 == "stage" ]]; then
  ec2_host="apistage.humansis.org"
  JWT_CERT=$JWT_CERT_STAGE
  JWT_KEY=$JWT_KEY_STAGE
  mv docker/docker-compose.yml.stage docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_STAGE} ${RDS_DB_NAME_STAGE} ${RDS_USERNAME_STAGE} ${RDS_PASSWORD_STAGE} ${MOBILE_KEY_STAGE} ${MOBILE_APP_VERSION_STAGE} ${MOBILE_APP_ID_STAGE} ${JWT_PASSPHRASE_STAGE} ${GELF_SERVER_NAME_STAGE}
elif [[ $1 == "test" ]]; then
  ec2_host="apitest.humansis.org"
  JWT_CERT=$JWT_CERT_TEST
  JWT_KEY=$JWT_KEY_TEST
  mv docker/docker-compose.yml.test docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_TEST} ${RDS_DB_NAME_TEST} ${RDS_USERNAME_TEST} ${RDS_PASSWORD_TEST} ${MOBILE_KEY_TEST} ${MOBILE_APP_VERSION_TEST} ${MOBILE_APP_ID_TEST} ${JWT_PASSPHRASE_TEST} ${GELF_SERVER_NAME_TEST}
elif [[ $1 == "dev" ]]; then
  ec2_host="apidev.humansis.org"
  JWT_CERT=$JWT_CERT_DEV
  JWT_KEY=$JWT_KEY_DEV
  mv docker/docker-compose.yml.dev docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DEV} ${RDS_DB_NAME_DEV} ${RDS_USERNAME_DEV} ${RDS_PASSWORD_DEV} ${MOBILE_KEY_DEV} ${MOBILE_APP_VERSION_DEV} ${MOBILE_APP_ID_DEV} ${JWT_PASSPHRASE_DEV} ${GELF_SERVER_NAME_DEV}
elif [[ $1 == "proddca" ]]; then # DCA
  ec2_user="admin"
  ec2_host="api.dca.humansis.org"
  JWT_CERT=$JWT_CERT_PRODDCA
  JWT_KEY=$JWT_KEY_PRODDCA
  mv docker/docker-compose.yml.template docker-compose.yml
  sed -i -e "s|%env(EC2_HOSTNAME)%|${ec2_host}|g" docker-compose.yml
  bash apply_env_config.sh ${RDS_HOSTNAME_DCA_PROD} ${RDS_DB_NAME_DCA_PROD} ${RDS_USERNAME_DCA_PROD} ${RDS_PASSWORD_DCA_PROD} ${MOBILE_KEY_DCA_PROD} ${MOBILE_APP_VERSION_DCA_PROD} ${MOBILE_APP_ID_DCA_PROD} ${JWT_PASSPHRASE_PRODDCA} ${GELF_SERVER_NAME_PRODDCA}
elif [[ $1 == "testdca" ]]; then # DCA
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

# add host to known_hosts
if [[ -z `ssh-keygen -F $ec2_host` ]]; then
  ssh-keyscan -H $ec2_host >> ~/.ssh/known_hosts
fi

# get app version
echo "Getting application information"
bash get_info.sh
echo "...done"

# deploy files to host
echo "Upload application files to remote server"
rsync --progress -avz --no-perms -e "ssh" --exclude 'ec2_bms.pem' --exclude-from='sync_excludes' ./* $ec2_user@$ec2_host:/var/www/html/bms_api/ --delete
echo "...done"
echo "Uploading JWT keypair"
ssh $ec2_user@$ec2_host "mkdir -p /var/www/html/bms_api/app/config/jwt/"
rsync --chmod=u+rw,g-rwx,o-rwx $JWT_KEY $ec2_user@$ec2_host:/var/www/html/bms_api/app/config/jwt/private.pem
rsync --chmod=u+rw,g+rw,o+r $JWT_CERT $ec2_user@$ec2_host:/var/www/html/bms_api/app/config/jwt/public.pem
echo "...done"
echo "Starting application containers"
start_app="cd /var/www/html/bms_api && sudo docker-compose up -d"
ssh $ec2_user@$ec2_host $start_app
echo "...done"
echo "Loading composer files"
load_composer="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'composer install'"
ssh $ec2_user@$ec2_host $load_composer
echo "...done"

# clean database
echo "Cleaning database"
if [[ $2 == "true" ]]; then
  clean_database="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'bash clean_database.sh migrations'"
  ssh $ec2_user@$ec2_host $clean_database
elif [[ $2 == "database" ]]; then
  clean_database="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'bash clean_database.sh'"
  ssh $ec2_user@$ec2_host $clean_database
  # get database
  bash get_db.sh ${RDS_HOSTNAME_STAGE} ${DB_DEPLOY_USER} ${DB_DEPLOY_USER_PASSWORD} ${DB_DEPLOY_NAME} "$1"
  # run database migrations
  migrations="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'"
  ssh $ec2_user@$ec2_host $migrations
elif [[ $2 == "false" ]]; then
  # run database migrations
  migrations="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:migrations:migrate -n'"
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
    load_fixtures="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:fixtures:load -n --env=dev'"
  elif [[ $3 == "test" ]]; then
    load_fixtures="cd /var/www/html/bms_api && sudo docker-compose exec -T php bash -c 'php bin/console doctrine:fixtures:load -n --env=test'"
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
cache_clear="cd /var/www/html/bms_api && bash ./clear_cache.sh $4"
ssh $ec2_user@$ec2_host "$cache_clear" || exit 1
echo "...done"

if [[ $1 == "dev" || $1 == "test" ]]; then
  if [[ $CRONTAB =~ ^(((([0-9]+,)+[0-9]+|([0-9]+(\/|-)[0-9]+)|[0-9]+|\*|(\*\/[0-9]+)) ?){5,7}) ]]; then
    echo "$CRONTAB" > crontab
    scp crontab $ec2_user@$ec2_host:/tmp
    add_cron="export CRONTAB=\"\$(cat /tmp/crontab)\" && envsubst < /opt/pin-import-template > /tmp/pin-import && sudo cp /tmp/pin-import /etc/cron.d/"
    ssh $ec2_user@$ec2_host $add_cron
  else
    echo "Wrong CRONTAB time format."
  fi
fi
