#!/bin/bash
set -x #echo all commands to cli
set -e #continue on error
#Image reset script

#Use the next line only for a distribution build, do not use on development versions!!! It clears the pacman history, makes a lot of space free, but that history is useful.

#pacman -Sc
#---
#Before running the script...
#Connect via Wired ethernet, remove all WiFi profiles.
#Dismount all NAS and USB sources, clear all NAS information. Unplug USB sources.
#Set automatic MPD rebuild ON in the Sources Menu.
#Reset the image using the following commands, some commands may fail (e.g. samba not installed), no problem.
#
# set up services
systemctl disable ashuffle mpd mpdscribble nmbd smbd udevil upmpdcli hostapd shairport-sync local-browser
systemctl enable avahi-daemon haveged nginx ntpd php-fpm redis rune_PL_wrk rune_SY_wrk sshd systemd-resolved
systemctl stop ashuffle mpd spopd smbd nmbd shairport-sync local-browser
#
# remove user files and logs
rm -f /var/lib/mpd/mpd.db
rm -f /var/lib/mpd/mpdstate
rm -rf /var/log/*
rm -rf /root/.*
rm -rf /var/www/test
rm -rf /mnt/MPD/LocalStorage/*
rm -rf /mnt/MPD/Webradio/*
rm -rf /var/lib/mpd/playlists/*
rm -rf /var/lib/mpd/playlists/RandomPlayPlaylist.m3u
#
# redis reset
php -f /srv/http/db/redis_datastore_setup reset
redis-cli set playerid ""
redis-cli set hwplatformid ""
#
# remover any git or samba passwords/email
git config -f /var/www/.git/config user.name ""
git config -f /var/www/.git/config user.email ""
pdbedit -L | grep -o ^[^:]* | smbpasswd -x
#
# The following commands should also be run after a system update or any package updates
# Reset the service and configuration files to the distribution standard
rm -f /etc/samba/*.conf
cp /var/www/app/config/defaults/smb-dev.conf /etc/samba/smb-dev.conf
cp /var/www/app/config/defaults/smb-prod.conf /etc/samba/smb-prod.conf
cp /var/www/app/config/defaults/ashuffle.service /usr/lib/systemd/system/ashuffle.service
cp /var/www/app/config/defaults/shairport-sync.service /usr/lib/systemd/system/shairport-sync.service
cp /var/www/app/config/defaults/spopd.service /usr/lib/systemd/system/spopd.service
cp /var/www/app/config/defaults/local-browser.service /usr/lib/systemd/system/local-browser.service
cp /var/www/app/config/defaults/redis.service /usr/lib/systemd/system/redis.service
cp /var/www/app/config/defaults/php-fpm.service /usr/lib/systemd/system/php-fpm.service
cp /var/www/app/config/defaults/upmpdcli.service /usr/lib/systemd/system/upmpdcli.service
cp /var/www/app/config/defaults/shairport-sync.conf /etc/shairport-sync.conf 
cp /var/www/app/config/defaults/spopd.conf /etc/spop/spopd.conf
cp /var/www/app/config/defaults/mpdscribble.conf /etc/mpdscribble.conf
cp /var/www/app/config/defaults/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf
cp /var/www/app/config/defaults/redis.conf /etc/redis.conf
cp /var/www/app/config/defaults/php-fpm.conf /etc/php/php-fpm.conf
#
# network
rm -f /etc/netctl/*
cp /var/www/app/config/defaults/eth0 /etc/netctl/eth0
cp /var/www/app/config/defaults/test /etc/netctl/test
#
# config.txt
cp /var/www/app/config/defaults/config.txt /boot/config.txt
#
# remove logs, run poweroff script and remove mounts
rm -rf /var/log/runeaudio/*
/var/www/command/rune_shutdown poweroff
rm -rf /mnt/MPD/NAS/*
#
# file protection and ownership
chown -R http.http /srv/http/
chmod -R 644 /srv/http/
find /srv/http/ -type f | xargs chmod 644
find /srv/http/ -type d | xargs chmod 755
chmod 777 /run
chmod 755 /srv/http/command/*
chmod 755 /srv/http/db/redis_datastore_setup
chmod 755 /srv/http/db/redis_acards_details
chown -R mpd.audio /var/lib/mpd
#
# zero fill the file system
cd /boot
sync
cat /dev/zero > zero.file
sync
rm zero.file
sync
cd /
cat /dev/zero > zero.file
sync
rm zero.file
sync
cd /home
#
# shutdown
shutdown -P now
#---
#End script
