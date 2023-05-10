#!/usr/bin/sh

DEST=/etc/systemd/system/grid-monitor.service
SRC=/srv/tasks/bin/grid-monitor.service

cp $SRC $DEST

systemctl daemon-reload
systemctl enable grid-monitor
service grid-monitor start