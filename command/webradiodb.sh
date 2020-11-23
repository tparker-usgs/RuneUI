#!/bin/bash

#rm $0

#[[ ! -e /srv/http/addonstitle.sh ]] && wget -q https://github.com/rern/RuneAudio_Addons/raw/master/srv/http/addonstitle.sh -P /srv/http
#. /srv/http/addonstitle.sh

# if sub directories
if ls -d /mnt/MPD/Webradio/*/ &> /dev/null; then
    # -mindepth 2 = in sub directories && -type f = file
    find /mnt/MPD/Webradio -mindepth 2 -type f -name *.pls -exec cp -f -- '{}' /mnt/MPD/Webradio \;
    # * = all sub directory && .[^.] = not ..
    rm -rf /mnt/MPD/Webradio/{*,.[^.]}/
fi

if ! ls /mnt/MPD/Webradio/*.pls &> /dev/null; then
    title -l '=' "$info No webradio files found."
    title -nt 'Copy *.pls to /mnt/MPD/Webradio/ then run again.'
    exit
fi

#title -l '=' "$bar Webradio Import ..."

# clear database
redis-cli del webradios &> /dev/null

# add data from files
for file in /mnt/MPD/Webradio/*.pls; do
    name=$( basename "$file" )
    name=${name%.*}
    url=$( grep 'File1' "$file" | cut -d '=' -f2 )

    redis-cli hset webradios "$name" "$url" &> /dev/null
    printf "%-30s : $url\n" "$name"
done

# refresh list
mpc update Webradio &> /dev/null

#title -l '=' "$bar Webradio imported successfully."
