#!/bin/bash

if [ ! -d vendor ]; then
    composer install
fi

# Start cron
#cron-launch

# Wait for database to be ready
available=`bin/console doctrine:migrations:status | awk 'NR==19' | cut -d " " -f 44`

while [ ! "$available" ]
do
    sleep 5
    available=`bin/console doctrine:migrations:status | awk 'NR==19' | cut -d " " -f 44`
done

set -e

# Execute the migrations
if [ $available -gt 0 ]
then
    bin/console doctrine:migrations:migrate
fi

# Generate JWT private/public keys
bin/console lexik:jwt:generate-keypair --skip-if-exists

# supervisorctl reread
# supervisorctl update
# supervisorctl start messenger-consume:*

php-fpm
