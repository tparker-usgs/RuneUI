#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#
# switch chrony on script (switch_chronyd_time_on.sh)
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
redis-cli hSet NTPtime systemd 'systemd time: stopped, disabled'
#
# set the ntp server values to default
redis-cli del ntpserver
/srv/http/db/redis_datastore_setup check
#
# start and enable chrony
systemctl enable chronyd
systemctl start chronyd
redis-cli hSet NTPtime chronyd 'chronyd time: active, enabled'
timedatectl set-ntp true
redis-cli set ashuffle_start_delay 30
#---
#End script
