#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#
# switch chrony on script (switch_chrony_time_on.sh)
# ----------------------- --------------------------
# also see switch systemd-time on (switch_systemd_time_on.sh)
#
# copy the config file
cp /var/www/app/config/defaults/chrony.conf /etc/chrony.conf
chmod 644 /etc/chrony.conf
#
# stop and disable systemd-timesyncd
timedatectl set-ntp false
systemctl stop systemd-timesyncd
systemctl disable systemd-timesyncd
#
# set the ntp server values to default
redis-cli del ntpserver
/srv/http/db/redis_datastore_setup check
#
# start and enable chrony
systemctl enable chronyd
systemctl start chronyd
timedatectl set-ntp true
#---
#End script
