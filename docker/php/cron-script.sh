#!/bin/bash

export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

if [[ "$(date --date tomorrow +\%d)" == "16" ]] ; then
    pwd >> /var/log/result 2>&1
    cd /var/www/html/symfony
    pwd >> /var/log/result 2>&1
    php bin/console reporting:data-indicator:add >> /var/log/result 2>&1
fi