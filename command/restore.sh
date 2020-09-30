#!/bin/bash
set -x # echo commands
set +e # continue on errors

redis-cli shutdown save
systemctl stop redis udevil ashuffle upmpdcli mpdscribble mpd nginx local-browser spotifyd shairport-sync spopd smbd smb nmbd nmb rune_PL_wrk rune_SSM_wrk
bsdtar -xpf $1 -C /
systemctl daemon-reload
systemctl start redis mpd
chown -R http.http /srv/http/
find /srv/http/ -type f -exec chmod 644 {} \;
find /srv/http/ -type d -exec chmod 755 {} \;
find /etc -name *.conf -exec chmod 644 {} \;
find /usr/lib/systemd/system -name *.service -exec chmod 644 {} \;
chmod 644 /etc/nginx/html/50x.html
chmod 777 /run
chmod 755 /srv/http/command/*
chmod 755 /srv/http/db/redis_datastore_setup
chmod 755 /srv/http/db/redis_acards_details
chmod 755 /etc/X11/xinit/start_chromium.sh
chown -R mpd.audio /var/lib/mpd
hostnm=$( redis-cli get hostname )
hostnm=${hostnm,,}
hostnamectl set-hostname $hostnm
timezn=$( redis-cli get timezone )
timedatectl set-timezone $timezn
sed -i "s/opcache.enable=./opcache.enable=$( redis-cli get opcache )/" /etc/php/conf.d/opcache.ini
rm $1
sleep 5
/srv/http/db/redis_datastore_setup check
set +e
count=$( cat /srv/http/app/templates/header.php | grep -c '$this->hostname' )
if [ $count -gt 2 ]
then
    redis-cli set playernamemenu '1'
else
    redis-cli set playernamemenu '0'
fi
redis-cli set restoreact '0'
mpc update Webradio
sleep 5
redis-cli shutdown save
sleep 5
mpd --kill
sleep 5
umount -Rf /mnt/MPD/NAS/*
umount -Rf /mnt/MPD/USB/*
rmdir /mnt/MPD/NAS/*
rmdir /mnt/MPD/USB/*
reboot
