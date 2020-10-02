#!/usr/bin/env bash

# parameters:
# $1: database host address
# $2: database name
# $3: database user
# $4: database user password

cp app/config/parameters.yml.dist app/config/parameters.yml
# common
sed -i -e "s|%env(RDS_PORT)%|${DB_PORT}|g" \
  -e "s|%env(SES_USERNAME)%|${MAILER_USER}|g" \
  -e "s|%env(SES_PASSWORD)%|${MAILER_PASSWORD}|g" \
  -e "s|%env(AWS_ACCESS_KEY)%|${aws_access_key_id}|g" \
  -e "s|%env(AWS_SECRET_KEY)%|${aws_secret_access_key}|g" \
  -e "s|%env(HID_SECRET)%|${HID_SECRET}|g" \
  -e "s|%env(GOOGLE_CLIENT)%|${GOOGLE_CLIENT}|g" app/config/parameters.yml

# per environment
sed -i -e "s|%env(RDS_HOSTNAME)%|$1|g" \
  -e "s|%env(RDS_DB_NAME)%|$2|g" \
  -e "s|%env(RDS_USERNAME)%|$3|g" \
  -e "s|%env(RDS_PASSWORD)%|$4|g" app/config/parameters.yml

sed -i -e "s|%env(RDS_HOSTNAME)%|$1|g" docker-compose.yml
