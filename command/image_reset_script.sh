#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#Image reset script

#Use the next line only for a distribution build, do not use on development versions!!! It clears the pacman history, makes a lot of space free, but that history is useful.
if [ "$1" == "full" ]; then
	echo "Running full cleanup and image initialisation"
	echo "Removing pacman cache"
	pacman -Sc --noconfirm
else
	echo "Running quick image initialisation"
fi
#---
#Before running the script...
#Connect via Wired ethernet, remove all WiFi profiles.
#Dismount all NAS and USB sources, clear all NAS information. Unplug USB sources.
#Reset the image using the following commands, some commands may fail (e.g. local-browser not installed), no problem.
#
# clean up any no longer valid mounts
udevil clean
#
# set up services and stop them
systemctl unmask systemd-journald
systemctl disable ashuffle mpd mpdscribble nmbd smbd udevil upmpdcli hostapd shairport-sync local-browser rune_SSM_wrk rune_PL_wrk dhcpcd systemd-timesyncd ntpd bluetooth
systemctl enable avahi-daemon haveged nginx php-fpm redis rune_SY_wrk sshd systemd-resolved systemd-journald chronyd
systemctl stop ashuffle mpd spopd smbd nmbd shairport-sync local-browser rune_SSM_wrk upmpdcli bluetooth
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
rm -rf /srv/http/tmp
rm -f /etc/sudoers.d/*
#
# reset web ui title back to default
sed -i  '/<title>/c\    <title>RuneAudio - RuneUI</title>' /srv/http/app/templates/header.php
#
# redis reset
redis-cli del AccessPoint
redis-cli del airplay
redis-cli del dlna
redis-cli del spotify
redis-cli del mpdconf
redis-cli del jamendo
redis-cli del dirble
redis-cli del samba
redis-cli del lyrics
redis-cli del nics
redis-cli del addons
redis-cli del addo
redis-cli del usbmounts
php -f /srv/http/db/redis_datastore_setup reset
redis-cli set playerid ""
redis-cli set hwplatformid ""
#
# update local git
rm -f /var/www/command/mpd-watchdog
cd /srv/http/
git stash
git add .
git pull
git config user.email "any@body.com"
git config user.name "anybody"
git stash
git add .
git pull
git stash
cd /home
#
# remove any git user-names & email
cd /srv/http/
git config user.name ""
git config user.email ""
cd /home
#
# remove any samba passwords
pdbedit -L | grep -o ^[^:]* | smbpasswd -x
#
# The following commands should also be run after a system update or any package updates
# Reset the service and configuration files to the distribution standard
rm -f /etc/samba/*.conf
cp /var/www/app/config/defaults/smb-dev.conf /etc/samba/smb-dev.conf
cp /var/www/app/config/defaults/smb-prod.conf /etc/samba/smb-prod.conf
ln -s /etc/samba/smb-prod.conf /etc/samba/smb.conf
cp /var/www/app/config/defaults/rune_SY_wrk.service /usr/lib/systemd/system/rune_SY_wrk.service
cp /var/www/app/config/defaults/rune_PL_wrk.service /usr/lib/systemd/system/rune_PL_wrk.service
cp /var/www/app/config/defaults/rune_SSM_wrk.service /usr/lib/systemd/system/rune_SSM_wrk.service
cp /var/www/app/config/defaults/ashuffle.service /usr/lib/systemd/system/ashuffle.service
cp /var/www/app/config/defaults/shairport-sync.service /usr/lib/systemd/system/shairport-sync.service
cp /var/www/app/config/defaults/spopd.service /usr/lib/systemd/system/spopd.service
cp /var/www/app/config/defaults/local-browser.service /usr/lib/systemd/system/local-browser.service
cp /var/www/app/config/defaults/redis.service /usr/lib/systemd/system/redis.service
cp /var/www/app/config/defaults/php-fpm.service /usr/lib/systemd/system/php-fpm.service
cp /var/www/app/config/defaults/upmpdcli.service /usr/lib/systemd/system/upmpdcli.service
cp /var/www/app/config/defaults/shairport-sync.conf /etc/shairport-sync.conf
cp /var/www/app/config/defaults/avahi_runeaudio.service /etc/avahi/services/runeaudio.service
cp /var/www/app/config/defaults/udevil.service /usr/lib/systemd/system/udevil.service
cp /var/www/app/config/defaults/hostapd.conf /etc/hostapd/hostapd.conf
cp /var/www/app/config/defaults/spopd.conf /etc/spop/spopd.conf
cp /var/www/app/config/defaults/mpdscribble.conf /etc/mpdscribble.conf
cp /var/www/app/config/defaults/wpa_supplicant.conf /etc/wpa_supplicant/wpa_supplicant.conf
cp /var/www/app/config/defaults/redis.conf /etc/redis.conf
cp /var/www/app/config/defaults/php-fpm.conf /etc/php/php-fpm.conf
cp /var/www/app/config/defaults/journald.conf /etc/systemd/journald.conf
cp /var/www/app/config/defaults/nsswitch.conf /etc/nsswitch.conf
cp /var/www/app/config/defaults/chrony.conf /etc/chrony.conf
cp /var/www/app/config/defaults/upmpdcli.conf /etc/upmpdcli.conf
cp /var/www/app/config/defaults/fstab /etc/fstab
cp /var/www/app/config/defaults/hosts /etc/hosts
#
# network
rm -f /etc/netctl/*
cp /var/www/app/config/defaults/eth0 /etc/netctl/eth0
cp /var/www/app/config/defaults/test /etc/netctl/test
#
# copy a standard config.txt
cp /var/www/app/config/defaults/config.txt /boot/config.txt
#
# remove logs, run poweroff script and remove mounts
rm -rf /var/log/runeaudio/*
/var/www/command/rune_shutdown poweroff
rm -rf /mnt/MPD/NAS/*
#
# file protection and ownership
# (the find commands are slow but work with spaces in file-names, required for chrome/xorg)
chown -R http.http /srv/http/
find /srv/http/ -type f -exec chmod 644 {} \;
find /srv/http/ -type d -exec chmod 755 {} \;
chmod 777 /run
chmod 755 /srv/http/command/*
chmod 755 /srv/http/db/redis_datastore_setup
chmod 755 /srv/http/db/redis_acards_details
chown -R mpd.audio /var/lib/mpd
#
# reset services so that any cached files are replaced by the latest ones (in case you don't want to reboot)
systemctl daemon-reload
#
#
if [ "$1" == "full" ]; then
	echo "Zero filling the file system"
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
fi
#
# reset root password
echo -e "rune\nrune" | passwd root
#
# reset host information (icon-name, chassis and hostname)
hostnamectl --static --transient --pretty set-icon-name multimedia-player
hostnamectl --static --transient --pretty set-chassis embedded
hostnamectl --static --transient --pretty set-hostname runeaudio
#
# set timezone to -11 hours of GMT - any user adjustment will always go forward
timedatectl set-timezone Pacific/Pago_Pago
redis-cli set timezone "Pacific/Pago_Pago"
#
# shutdown redis and force a write all in-memory keys to disk (purges any cached values)
redis-cli save
redis-cli shutdown save
#
# shutdown & poweroff
shutdown -P now
#---
#End script
