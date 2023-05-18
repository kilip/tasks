#!/bin/bash

SHUTDOWN_LOCK=/srv/tasks/var/shutdown.lck
LOCKFILE=/srv/tasks/var/check-grid.lck

if [[ -f $LOCKFILE ]]; then
    unlink $LOCKFILE
fi

while true
do
    /srv/tasks/bin/console tasks:check-grid
    if [[ -f $SHUTDOWN_LOCK ]]; then
        #sudo systemctl suspend
        exit 0
    else
        sleep 5                       ## wait 10 sec before repeating
    fi
done
