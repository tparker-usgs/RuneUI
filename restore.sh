#!/bin/bash

systemctl stop mpd redis rune_PL_wrk rune_SSM_wrk ashuffle spopd shairport-sync upmpdcli mpdscribble
bsdtar -xpf $1 -C /
systemctl start redis mpd
hostnm=$( redis-cli get hostname )
hostnm=${hostnm,,}
hostnamectl set-hostname $hostnm
sed -i "s/opcache.enable=./opcache.enable=$( redis-cli get opcache )/" /etc/php/conf.d/opcache.ini
mpc update Webradio
/srv/http/db/redis_datastore_setup check
systemctl restart rune_SY_wrk || systemctl start rune_SY_wrk

rm $1
