#!/bin/bash

export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

if [[ "$(date --date tomorrow +\%d)" == "01" ]] ; then
    cd /var/www/html/symfony
    php bin/console reporting:data-indicator:add
fi