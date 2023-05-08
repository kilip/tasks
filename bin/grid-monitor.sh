#!/bin/bash

SHUTDOWN_LOCK=/srv/tasks/var/shutdown.lck

while true
do
    /srv/tasks/bin/console tasks:check-grid -vvv
    if [[ -f $SHUTDOWN_LOCK ]]; then
        sudo poweroff -p
        exit 0
    else
        sleep 5                       ## wait 10 sec before repeating
    fi
done
