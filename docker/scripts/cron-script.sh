#!/bin/bash

export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

cd /var/www/html
php bin/console reporting:data-indicator:add
php bin/console audit:clean 1 --no-confirm
