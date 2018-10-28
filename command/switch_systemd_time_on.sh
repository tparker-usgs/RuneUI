#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#
# switch systemd-time on (switch_systemd_time_on.sh)
# ---------------------- ---------------------------
# also see switch chrony on script (switch_chronyd_time_on.sh)
#
# copy the config file
cp /var/www/app/config/defaults/timesyncd.conf /etc/systemd/timesyncd.conf
chmod 644 /etc/systemd/timesyncd.conf
#
# stop and disable chrony
timedatectl set-ntp false
systemctl stop chronyd
systemctl disable chronyd
redis-cli hSet NTPtime chronyd 'chronyd time: stopped, disabled'
#
# set the ntp server values to default
redis-cli del ntpserver
/srv/http/db/redis_datastore_setup check
#
# start and enable systemd-timesyncd
systemctl enable systemd-timesyncd
systemctl start systemd-timesyncd
redis-cli hSet NTPtime systemd 'systemd time: active, enabled'
timedatectl hSet set-ntp true
redis-cli set ashuffle_start_delay 5
#---
#End script
