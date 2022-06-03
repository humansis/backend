#!/usr/bin/env bash

# parameters:
# $1: database host address
# $2: database name
# $3: database user
# $4: database user password
# $5: mobile app master key
# $6: mobile app version
# $7: mobile app id
# $8: jwt passphrase
# $9: server hostname


# common
sed -i -e "s|%env(RDS_PORT)%|${DB_PORT}|g" \
  -e "s|%env(SES_USERNAME)%|${MAILER_USER}|g" \
  -e "s|%env(SES_PASSWORD)%|${MAILER_PASSWORD}|g" \
  -e "s|%env(AWS_ACCESS_KEY)%|${aws_access_key_id}|g" \
  -e "s|%env(AWS_SECRET_KEY)%|${aws_secret_access_key}|g" \
  -e "s|%env(HID_SECRET)%|${HID_SECRET}|g" \
  -e "s|%env(GOOGLE_CLIENT)%|${GOOGLE_CLIENT}|g" \
  -e "s|%env(GELF_HOST)%|${GELF_HOST}|g" \
  -e "s|%env(AWS_LOGS_ACCESS_KEY)%|${AWS_LOGS_ACCESS_KEY}|g" \
  -e "s|%env(AWS_LOGS_SECRET_KEY)%|${AWS_LOGS_SECRET_KEY}|g" \
  -e "s|%env(GELF_PORT)%|${GELF_PORT}|g" app/config/parameters.yml

# per environment
sed -i -e "s|%env(RDS_HOSTNAME)%|$1|g" \
  -e "s|%env(RDS_DB_NAME)%|$2|g" \
  -e "s|%env(RDS_USERNAME)%|$3|g" \
  -e "s|%env(RDS_PASSWORD)%|$4|g" \
  -e "s|%env(MOBILE_MASTER_KEY)%|$5|g" \
  -e "s|%env(MOBILE_APP_VERSION)%|$6|g" \
  -e "s|%env(MOBILE_APP_ID)%|$7|g" \
  -e "s|%env(JWT_PASSPHRASE)%|${8}|g" \
  -e "s|%env(GELF_SERVER_NAME)%|${9}|g"\
  -e "s|%env(BATCH_SIZE_INTEGRITY_CHECK)%|${BATCH_SIZE_INTEGRITY_CHECK}|g" \
  -e "s|%env(BATCH_SIZE_IDENTITY_CHECK)%|${BATCH_SIZE_IDENTITY_CHECK}|g" \
  -e "s|%env(BATCH_SIZE_SIMILARITY_CHECK)%|${BATCH_SIZE_SIMILARITY_CHECK}|g" \
  -e "s|%env(BATCH_SIZE_FINALIZATION)%|${BATCH_SIZE_FINALIZATION}|g" \
  -e "s|%env(ENVIRONMENT)%|${ENVIRONMENT}|g" app/config/parameters.yml


sed -i -e "s|%env(RDS_HOSTNAME)%|$1|g" docker-compose.yml
