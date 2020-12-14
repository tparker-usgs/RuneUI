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
#  file: command/convert_dos_files_to_unix_script.sh
#  version: 1.3
#  coder: janui
#  date: December 2020
#
set -x # echo all commands to cli
set +e # continue on errors
#
# the work directories are created on each start up, most are in the tmpfs memory file system, see /etc/fstab
# backup could be created their, but is as default created in /home
#
# first create and initialise the backup directory specified by redis
#
# get the redis variable and make any duplicate trailing / into a single /
backupDir=$( redis-cli get backup_dir | tr -s / | xargs )
# remove a trailing / if it exists
backupDir="${backupDir%/}"
if [[ "$backupDir" != *"tmp"* ]] && [[ "$backupDir" != *"backup"* ]]; then
    # backupDir must contain 'tmp' or 'backup', it should then never interfere with the Linux or RuneAudio
    # otherwise ste it to default
    backupDir="/home/backup"
fi
# save the backupDir name in redis
redis-cli set backup_dir "$backupDir"
# create the directory , change the owner and privileges and delete its contents(if any)
mkdir -p "$backupDir"
chown -R http.http "$backupDir"
chmod -R 755 "$backupDir"
rm -fR "$backupDir/*"
#
# create and initialise the shairport-sync Airplay art directory
#
mkdir -p /srv/http/tmp/airplay
cp /srv/http/assets/img/airplay-default.png /srv/http/tmp/airplay/airplay-default.png
cp /srv/http/assets/img/cover-default-runeaudio.png /srv/http/tmp/airplay/airplay-none.png
cp /srv/http/assets/img/black.png /srv/http/tmp/airplay/black.png
chown -R http.http /srv/http/tmp
chmod 755 /srv/http/tmp/airplay
chmod -R 444 /srv/http/tmp/airplay/*
#
# create and initialise the shairport-sync Airplay art directory
#
mkdir -p /srv/http/tmp/spotify-connect
cp /srv/http/assets/img/spotify-connect-default.png /srv/http/tmp/spotify-connect/spotify-connect-default.png
cp /srv/http/assets/img/cover-default-runeaudio.png /srv/http/tmp/spotify-connect/spotify-connect-none.png
cp /srv/http/assets/img/black.png /srv/http/tmp/spotify-connect/black.png
chown -R http.http /srv/http/tmp
chmod 755 /srv/http/tmp/spotify-connect
chmod -R 444 /srv/http/tmp/spotify-connect/*
# if the spotify connect cache is defined create the directory
spotifyConnectCache=$( redis-cli hget spotifyconnect cache_path )
if [ "$spotifyConnectCache" != "" ]; then
    mkdir -p "$spotifyConnectCache"
    chown -R \spotifyd.spotifyd "$spotifyConnectCache"
    chmod 755 "$spotifyConnectCache"
    chmod -R 644 "$spotifyConnectCache/*"
fi
#
# create and initialise the MPD art directory
#
mkdir -p /srv/http/tmp/mpd
cp /srv/http/assets/img/cover-default-runeaudio.png /srv/http/tmp/mpd/mpd-default.png
cp /srv/http/assets/img/cover-default-runeaudio.png /srv/http/tmp/mpd/mpd-none.png
cp /srv/http/assets/img/black.png /srv/http/tmp/mpd/black.png
chown -R http.http /srv/http/tmp
chmod 755 /srv/http/tmp/mpd
chmod -R 444 /srv/http/tmp/mpd/*
