#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#
# switch systemd-time on (switch_systemd_time_on.sh)
# ---------------------- ---------------------------
# also see switch chrony on script (switch_chrony_time_on.sh)
#
# copy the config file
cp /var/www/app/config/defaults/timesyncd.conf /etc/systemd/timesyncd.conf
chmod 644 /etc/systemd/timesyncd.conf
#
# stop and disable chrony
timedatectl set-ntp false
systemctl stop chronyd
systemctl disable chronyd
#
# set the ntp server values to default
redis-cli del ntpserver
/srv/http/db/redis_datastore_setup check
#
# start and enable systemd-timesyncd
systemctl enable systemd-timesyncd
systemctl start systemd-timesyncd
timedatectl set-ntp true
#---
#End script
