#!/bin/bash
#
#  Copyright (C) 2013-2014 RuneAudio Team
#  http://www.runeaudio.com
#
#  RuneUI
#  copyright (C) 2013-2014 – Andrea Coiutti (aka ACX) & Simone De Gregori (aka Orion)
#
#  RuneOS
#  copyright (C) 2013-2014 – Simone De Gregori (aka Orion) & Carmelo San Giovanni (aka Um3ggh1U)
#
#  RuneAudio website and logo
#  copyright (C) 2013-2014 – ACX webdesign (Andrea Coiutti)
#
#  This Program is free software; you can redistribute it and/or modify
#  it under the terms of the GNU General Public License as published by
#  the Free Software Foundation; either version 3, or (at your option)
#  any later version.
#
#  This Program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with RuneAudio; see the file COPYING. If not, see
#  <http://www.gnu.org/licenses/gpl-3.0.txt>.
#
#  file: command/restore.sh
#  version: 1.3
#  coder: janui
#  date: October 2020
#
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
