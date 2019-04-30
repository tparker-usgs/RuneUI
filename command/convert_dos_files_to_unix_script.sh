#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
# Convert important files from dos format to unix format  script
# Parameter final removes the package dos2unix
#
# Install dos2unix if required
pacman -Q dos2unix || pacman -Sy dos2unix --noconfirm
#
# Dos2Unix conversion
cp /var/www/app/config/defaults/config.txt /tmp/config.txt
cd /var/www/app/config/defaults
dos2unix -k -s -o *
cp /tmp/config.txt /var/www/app/config/defaults/config.txt
rm /tmp/config.txt
cd /srv/http/assets/js
dos2unix -k -s -o *
cd /srv/http/db
dos2unix -k -s -o *
cd /srv/http/app
dos2unix -k -s -o *
cd /srv/http/app/templates
dos2unix -k -s -o *
cd /srv/http/app/libs
dos2unix -k -s -o *
cd /srv/http/command
dos2unix -k -s -o *
cd /srv/http
dos2unix -k -s -o *
cd /etc
dos2unix -k -s -o *.conf
cd /home
#
# Check file protections and ownership
chown -R http.http /srv/http/
find /srv/http/ -type f -exec chmod 644 {} \;
find /srv/http/ -type d -exec chmod 755 {} \;
find /etc -name *.conf -exec chmod 644 {} \;
unset array_suid_files
unset array_sticky_files
array_suid_files=( $(find /usr -perm /4000) )
array_sticky_files=( $(find /usr -perm /1000) )
chmod 755 /usr
chmod -R 755 /usr/share
chown -R root.root /usr
for i in "${array_suid_files[@]}"
do
	chmod +s "$i"
#	echo "chmod +s $i"
done
for i in "${array_sticky_files[@]}"
do
	chmod +t "$i"
#	echo "chmod +t $i"
done
unset array_suid_files
unset array_sticky_files
chmod +s /usr/bin/udevil
chmod +s /usr/bin/passwd
find /usr/lib/systemd/system -name *.service -exec chmod 644 {} \;
chmod 644 /etc/nginx/html/50x.html
chmod 777 /run
chmod 755 /srv/http/command/*
chmod 755 /srv/http/db/redis_datastore_setup
chmod 755 /srv/http/db/redis_acards_details
chmod 755 /etc/X11/xinit/start_chromium.sh
chown mpd.audio /mnt/MPD/*
chown mpd.audio /mnt/MPD/USB/*
chmod 777 /mnt/MPD/USB
chmod 777 /mnt/MPD/USB/*
chown -R mpd.audio /var/lib/mpd
#
# Remove dos2unix if requested
if [ "$1" == "final" ]; then
	echo "Removing dos2unix package"
	pacman -Rs dos2unix --noconfirm
fi
#---
#End script
