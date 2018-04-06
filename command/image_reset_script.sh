#!/bin/bash
#Image reset script

#Use the next line only for a distribution build, do not use on development versions!!! It clears the pacman history, makes a lot of space free, but that history is useful.

#pacman -Sc
#---
#Before running the script...
#Connect via Wired ethernet, remove all WiFi profiles.
#Dismount all NAS and USB sources, clear all NAS information. Unplug USB sources.
#Set automatic MPD rebuild ON in the Sources Menu.
#Reset the image using the following commands, some commands may fail (e.g. samba not installed), no problem.

systemctl disable ashuffle mpd mpdscribble nmbd smbd udevil upmpdcli hostapd shairport-sync local.browser
systemctl enable avahi-daemon haveged nginx ntpd php-fpm redis rune_PL_wrk rune_SY_wrk sshd systemd-resolved
systemctl stop ashuffle mpd spopd smbd nmbd shairport-sync local.browser
rm -f /var/lib/mpd/mpd.db
rm -f /var/lib/mpd/mpdstate
rm -rf /var/log/*
rm -rf /root/.*
rm -rf /var/www/test
rm -rf /mnt/MPD/LocalStorage/*
rm -rf /mnt/MPD/Webradio/*
rm -rf /var/lib/mpd/playlists/*
rm -rf /var/lib/mpd/playlists/RandomPlayPlaylist.m3u
redis-cli set playerid ""
redis-cli set hwplatformid ""
php -f /srv/http/db/redis_datastore_setup reset
git config -f /var/www/.git/config user.name ""
git config -f /var/www/.git/config user.email ""
rm -rf //etc/samba/smb-dev.conf /etc/samba/smb-dist.conf /etc/samba/smb-prod.conf
cp /var/www/app/config/defaults/smb-dev.conf /etc/samba/smb-dev.conf
cp /var/www/app/config/defaults/smb-prod.conf /etc/samba/smb-prod.conf
pdbedit -L | grep -o ^[^:]* | smbpasswd -x
# The following files should also be copied after a system update
cp /var/www/app/config/defaults/ashuffle.service /usr/lib/systemd/system/ashuffle.service
cp /var/www/app/config/defaults/shairport-sync.service /usr/lib/systemd/system/shairport-sync.service
cp /var/www/app/config/defaults/spopd.service /usr/lib/systemd/system/spopd.service
cp /var/www/app/config/defaults/local-browser.service /usr/lib/systemd/system/local-browser.service
cp /var/www/app/config/defaults/redis.service /usr/lib/systemd/system/redis.service
cp /var/www/app/config/defaults/php-fpm.service /usr/lib/systemd/system/php-fpm.service
cp /var/www/app/config/defaults/shairport-sync.conf /etc/shairport-sync.conf 
cp /var/www/app/config/defaults/spopd.conf /etc/spop/spopd.conf
cp /var/www/app/config/defaults/mpdscribble.conf /etc/mpdscribble.conf
cp /var/www/app/config/defaults/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf
cp /var/www/app/config/defaults/redis.conf /etc/redis.conf
cp /var/www/app/config/defaults/php-fpm.conf /etc/php/php-fpm.conf
# end system update copy
rm -f /etc/netctl/*
cp /var/www/app/config/defaults/eth0 /etc/netctl/eth0
cp /var/www/app/config/defaults/test /etc/netctl/test
php /srv/http/db/redis_datastore_setup reset
rm -rf /var/log/runeaudio/*
/var/www/command/rune_shutdown poweroff
rm -rf /mnt/MPD/NAS/*
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
shutdown -P now
#---
#End script
