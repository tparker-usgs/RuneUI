#!/bin/bash

redis-cli shutdown save
systemctl stop mpd redis rune_PL_wrk rune_SSM_wrk ashuffle spopd shairport-sync upmpdcli mpdscribble local-browser udevil
bsdtar -xpf $1 -C /
systemctl daemon-reload
systemctl start redis mpd
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
umount -aft nfs
umount -aft cifs
reboot
