#!/bin/bash

# Fix DNSSEC script
#
# If DNSSEC is switched on the nts time servers will be accessable at boot because time is incorrect.
# The workaround is to let RuneAudio boot with DNSSEC switched off and after a timesync has taken place to
# restart systemd resolve with DNSSEC switched on. After restarting systemd resolve the resolved
# configuration file is modified to switch DNSSEC off for the next boot.

# first check that a timesync has taken place, maybe there is no intenet connection
timesync_yes=$( timedatectl show -a | grep NTPSynchronized | grep -ci yes )
# also check that DNSSEC is still switched off, maybe this routine has already been run
dnssec_yes=$( resolvectl status | grep -i 'dnssec setting' | grep -ci yes )
if [ "$timesync_yes" = "0" ];
then
    # not timesync'd
    if [ "$dnssec_yes" != "0" ];
    then
        # dnssec switched on, so switch it off
        # normally we would not get here
        sed -i '/DNSSEC=/c\DNSSEC=no' /etc/systemd/resolved.conf
        # restart systemd resolve
        systemctl restart systemd-resolved.service
    fi
else
    # timesync ok
    if [ "$dnssec_yes" = "0" ];
    then
        # dnssec switched off, so switch it on
        sed -i '/DNSSEC=/c\DNSSEC=yes' /etc/systemd/resolved.conf
        # restart systemd resolve
        systemctl restart systemd-resolved.service
        # switch it off in the configurartion file for the next reboot
        sed -i '/DNSSEC=/c\DNSSEC=no' /etc/systemd/resolved.conf
        exit
    fi
fi
