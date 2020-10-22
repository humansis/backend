#!/usr/bin/env bash

# parameters:
# $1: environment [dev, test, stage]
# download anonymized database dump
echo "Downloading database"
# --set-gtid-purged=OFF to make dump portable
mysqldump --set-gtid-purged=OFF -h ${RDS_HOSTNAME_STAGE} -u ${DB_DEPLOY_USER} -p"${DB_DEPLOY_USER_PASSWORD}" ${DB_DEPLOY_NAME} | pv > db.sql
echo "...done"

# upload anonymized database to proper host
echo "Uploading database"
if [[ $1 == "stage" ]]; then
  pv --bytes --eta --progress db.sql | mysql -h ${RDS_HOSTNAME_STAGE} -u ${RDS_USERNAME_STAGE} -p"${RDS_PASSWORD_STAGE}" ${RDS_DB_NAME_STAGE}
elif [[ $1 == "test" ]]; then
  pv --bytes --eta --progress db.sql | mysql -h ${RDS_HOSTNAME_TEST} -u ${RDS_USERNAME_TEST} -p"${RDS_PASSWORD_TEST}" ${RDS_DB_NAME_TEST}
elif [[ $1 == "dev" ]]; then
  pv --bytes --eta --progress db.sql | mysql -h ${RDS_HOSTNAME_DEV} -u ${RDS_USERNAME_DEV} -p"${RDS_PASSWORD_DEV}" ${RDS_DB_NAME_DEV}
else
  echo "Unable to upload database. Wrong environment selected. Options are: [dev, test, stage]"
  exit 1
fi
echo "...done"

# cleanup
rm db.sql
