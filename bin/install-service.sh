#!/usr/bin/sh

DEST=/etc/systemd/system/check-grid.service
SRC=/srv/tasks/bin/check-grid.service

cp $SRC $DEST

systemctl enable check-grid
service check-grid start