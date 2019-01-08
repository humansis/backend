#!/bin/bash

if (printf 'symfony\n' | sudo -S cron) ; then
    while true ; do
        sleep 1
    done
else
    exit 1
fi
