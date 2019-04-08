#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#
# Image reset script
if [ "$1" == "full" ]; then
	echo "Running full cleanup and image initialisation for a distribution image"
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
systemctl disable ashuffle mpd mpdscribble nmb smb winbind udevil upmpdcli hostapd shairport-sync local-browser rune_SSM_wrk rune_PL_wrk dhcpcd systemd-timesyncd php-fpm ntpd bluetooth bootsplash cronie
systemctl enable avahi-daemon haveged nginx redis rune_SY_wrk sshd systemd-resolved systemd-journald systemd-timesyncd
systemctl stop ashuffle mpd nmb smb winbind shairport-sync local-browser rune_SSM_wrk rune_PL_wrk rune_SY_wrk upmpdcli bluetooth systemd-timesyncd cronie udevil spotifyd
#
# run poweroff script (and remove network mounts)
/var/www/command/rune_shutdown poweroff
#
# unmount USB drives and delete the mount points
umount -R /mnt/MPD/USB/*
rmdir /mnt/MPD/USB/*
rm -fr /mnt/MPD/USB/*
#
# install raspi-rotate
/var/www/command/raspi-rotate-install.sh
#
# install spotifyd
/var/www/command/spotifyd-install.sh
#
# remove rerns addons menu (if installed)
systemctl stop addons cronie
systemctl disable addons cronie
rm -f /etc/systemd/system/addons.service
rm -f /etc/sudoers.d/http
rm -f /etc/sudoers.d/http-backup
rm -fr /home/rern
/usr/local/bin/uninstall_addo.sh
rm -f ./install.sh
rm -f /usr/local/bin/uninstall_addo.sh
rm -f /usr/local/bin/uninstall_enha.sh
redis-cli del addons
redis-cli del addo
#
# remove user files and logs
rm -rf /var/log/runeaudio/*
umount /srv/http/tmp
umount /var/log/runeaudio
umount /var/log
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
rm -rf /home/*
rm -rf /var/lib/bluetooth/*
#
# redis reset
redis-cli del AccessPoint
redis-cli del airplay
redis-cli del dirble
redis-cli del dlna
redis-cli del jamendo
redis-cli del lyrics
redis-cli del mpdconf
redis-cli del nics
redis-cli del samba
redis-cli del spotify
redis-cli del usbmounts
redis-cli del debugdata
redis-cli del local_browser
php -f /srv/http/db/redis_datastore_setup reset
redis-cli set playerid ""
redis-cli set hwplatformid ""
#
# update local git
rm -f /var/www/command/mpd-watchdog
cd /srv/http/
git config --global core.editor "nano"
git config user.email "any@body.com"
git config user.name "anybody"
git stash
git add .
git stash
git pull --no-edit
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
# reset root password
echo -e "rune\nrune" | passwd root
#
# reset the service and configuration files to the distribution standard
# the following commands should also be run after a system update or any package updates
rm -f /etc/nginx/nginx.conf
rm -f /etc/samba/*.conf
rm -f /etc/netctl/*
# copy default settings and services
cp -Rv /srv/http/app/config/defaults/etc/* /etc
cp -R /srv/http/app/config/defaults/usr/* /usr
# make appropriate links
ln -s /etc/nginx/nginx-prod.conf /etc/nginx/nginx.conf
ln -s /etc/samba/smb-prod.conf /etc/samba/smb.conf
#
# copy a standard config.txt
#cp /var/www/app/config/defaults/config.txt /boot/config.txt
#
# modify standard .service files
# not needed any more kg
#sed -i 's|.*PIDFile=/var/run.*/|PIDFile=/run/|g' /usr/lib/systemd/system/smb.service
#sed -i 's|.*PIDFile=/var/run.*/|PIDFile=/run/|g' /usr/lib/systemd/system/nmb.service
#sed -i 's|.*PIDFile=/var/run.*/|PIDFile=/run/|g' /usr/lib/systemd/system/winbind.service
#sed -i 's|.*User=mpd.*|#User=mpd|g' /usr/lib/systemd/system/mpd.service
#
# make sure that all files are unix format and have the correct ownerships and protections
# the 'final' option also removes the dos2unix package
if [ "$1" == "full" ]; then
	/srv/http/command/convert_dos_files_to_unix_script.sh final
else
	/srv/http/command/convert_dos_files_to_unix_script.sh
fi
#
# set lyrics and artistinfo for no internet connection
chown root.root /srv/http/command/lyric.sh
chown root.root /srv/http/command/artist_info.sh
chmod 600 /srv/http/command/lyric.sh
chmod 600 /srv/http/command/artist_info.sh
#
#for a distribution image remove the pacman history. It makes a lot of space free, but that history is useful when developing
if [ "$1" == "full" ]; then
	pacman -Sc --noconfirm
fi
#
# reset systemd services so that any cached files are replaced by the latest ones
systemctl daemon-reload
#
# zero fill the file system if parameter 'full' is selected
# this takes ages to run, but the zipped distribution image will then be very small
if [ "$1" == "full" ]; then
	redis-cli save
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
