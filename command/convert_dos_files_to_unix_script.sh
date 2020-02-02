#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#
# Convert important files from dos format to unix format script
# Parameter final removes the package dos2unix
#
# Install dos2unix if required
pacman -Q dos2unix || pacman -Sy dos2unix --noconfirm
#
# Dos2Unix conversion
#
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
# Convert leading tabs to 4 spaces is the files
#
echo "Convert leading tabs to 4 spaces is the files"
FILES="/srv/http/assets/js/*
/srv/http/db/*
/srv/http/app/*
/srv/http/app/template/*
/srv/http/app/libs/*
/srv/http/command/*
/srv/http/*"
shopt -s nullglob
for f in $FILES
do
    if [ -d "$f" ] ; then
        continue # its a directory not a file
    fi
    numltabs=$(grep -Pc "^\t" "$f")
    numlspacetabs=$(grep -Pc "^ *.\t" "$f")
    if [ "$numltabs" == "0" ] && [ "$numlspacetabs" == "0" ] ; then
        continue # no leading tab(s) or space(s) then tab(s) in the file
    fi
    expand -i -t4 "$f" > /home/file.temp
    cp /home/file.temp "$f"
    rm /home/file.temp
    # echo "Tabs to spaces: $f"
done
#
# When requested, remove trailing whitespace in lines from bin/bash files, but exclude vendor files
#
if [ "$1" == "cleanfiles" ]; then
    echo "Removing trailing whitespace from bin/bash files"
    FILES=$(grep -lr '^#!/bin/bash' /srv/http | grep -v '/vendor/')
    for f in $FILES
    do
        if [ -d "$f" ] ; then
            continue # its a directory not a file
        fi
        numstrpace=$(grep -c '[[:blank:]]$' "$f")
        if [ "$numstrpace" == "0" ] ; then
            continue # no trailing whitespace in the file
        fi
        # echo "Trailing whitespace bin/bash: $f"
        sed -i 's/[ \t]*$//' "$f"
    done
fi
#
# When requested, remove trailing whitespace from php files, but exclude vendor files
#
if [ "$1" == "cleanfiles" ]; then
    echo "Removing trailing whitespace from php files"
    FILES=$(grep -lr '^<?php' /srv/http | grep -v '/vendor/')
    for f in $FILES
    do
        if [ -d "$f" ] ; then
            continue # its a directory not a file
        fi
        numstrpace=$(grep -c '[[:blank:]]$' "$f")
        if [ "$numstrpace" == "0" ] ; then
            continue # no trailing whitespace in the file
        fi
        # echo "Trailing whitespace php: $f"
        sed -i 's/[ \t]*$//' "$f"
    done
fi
#
# Check file protections and ownership
#
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
chown mpd.audio /mnt/MPD/*
chown mpd.audio /mnt/MPD/USB/*
chmod 777 /mnt/MPD/USB
chmod 777 /mnt/MPD/USB/*
chown -R mpd.audio /var/lib/mpd
#
# Remove dos2unix if requested
#
if [ "$1" == "final" ]; then
    echo "Removing dos2unix package"
    pacman -Rs dos2unix --noconfirm
fi
#---
#End script
