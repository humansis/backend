#!/usr/bin/env bash

# parameters:
# $1: database host
# $2: database user
# $3: database user password
# $4: db to be copied
# $5: environment [dev, test, stage]
DB_HOST=$1
DB_USER=$2
export MYSQL_PWD=$3
DUMP_DB=$4
if [[ $5 == "stage" ]]; then
  COPY_DB=${RDS_DB_NAME_STAGE}
elif [[ $5 == "test" ]]; then
  COPY_DB=${RDS_DB_NAME_TEST}
elif [[ $5 == "dev" ]]; then
  COPY_DB=${RDS_DB_NAME_DEV}
else
  echo "Wrong environment parameter"
  exit 1
fi

echo "Database schema upload..."
mysqldump --no-data -h $DB_HOST -u $DB_USER $DUMP_DB > schema.sql
sed -i -e 's/DEFINER[ ]=[ ][^*]**/*/' schema.sql

# mysql -h $DB_HOST -u $DB_USER -e "CREATE DATABASE $COPY_DB"

mysql -h $DB_HOST -u $DB_USER $COPY_DB < schema.sql

echo "...done"
echo "Database data upload..."
for table in $(mysql -h $DB_HOST -u $DB_USER -e "SHOW TABLES" -sN $DUMP_DB);
  do
    echo "uploading table: $table"
    mysql -h $DB_HOST -u $DB_USER -e "SET FOREIGN_KEY_CHECKS=0; INSERT INTO $COPY_DB.$table SELECT * FROM $DUMP_DB.$table"
  done
echo "...done"

rm schema.sql
