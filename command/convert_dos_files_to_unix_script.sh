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
# exclude binary files, keep the date, keep the old file name
#
# 
# all files in the directory /var/www/app/config/defaults/ inclusive subdirectories
# exceptions are /boot/config.txt and /boot/wifi/* these stay in ms-dos format
cp /var/www/app/config/defaults/boot/config.txt /tmp/config.txt
cp -r /var/www/app/config/defaults/boot/wifi /tmp/wifi
cd /var/www/app/config/defaults
find /var/www/app/config/defaults/ -type f -exec dos2unix -k -s -o {} \;
cp /tmp/config.txt /var/www/app/config/defaults/boot/config.txt
cp -r /tmp/wifi /var/www/app/config/defaults/boot/wifi
rm /tmp/config.txt
rm -r /tmp/wifi
# all files in /srv/http/assets/js
cd /srv/http/assets/js
dos2unix -k -s -o *
# all files in /srv/http/db
cd /srv/http/db
dos2unix -k -s -o *
# all files in /srv/http/app
cd /srv/http/app
dos2unix -k -s -o *
# all files in /srv/http/app/templates
cd /srv/http/app/templates
dos2unix -k -s -o *
# all files in /srv/http/app/libs
cd /srv/http/app/libs
dos2unix -k -s -o *
# all files in /srv/http/command
cd /srv/http/command
dos2unix -k -s -o *
# all files in /srv/http
cd /srv/http
dos2unix -k -s -o *
# all files named *.conf in /etc and subdirectories 
cd /home
find /etc -type f -name *.conf -exec dos2unix -k -s -o {} \;
# the file /srv/http/assets/css/runeui.css
dos2unix -k -s -o /srv/http/assets/css/runeui.css
#
# Convert leading tabs to 4 spaces is the files
#
set +x # echo no commands to cli
echo "Convert leading tabs to 4 spaces is the files"
FILES="/srv/http/assets/js/*
/srv/http/db/*
/srv/http/app/*
/srv/http/app/templates/*
/srv/http/app/libs/*
/srv/http/command/*
/srv/http/*
/srv/http/assets/css/runeui.css"
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
    echo "Tabs to spaces: $f"
done
#
# When requested, remove trailing whitespace in lines from bin/bash files, but exclude vendor files
#
if [ "$1" == "cleanfiles" ] || [ "$2" == "cleanfiles" ]; then
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
        echo "Trailing whitespace bin/bash: $f"
        sed -i 's/[ \t]*$//' "$f"
    done
fi
#
# When requested, remove trailing whitespace from php files, but exclude vendor files
#
if [ "$1" == "cleanfiles" ] || [ "$2" == "cleanfiles" ]; then
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
        echo "Trailing whitespace php: $f"
        sed -i 's/[ \t]*$//' "$f"
    done
fi
#
# When requested, remove trailing whitespace from php files, but exclude vendor files
#
if [ "$1" == "cleanfiles" ] || [ "$2" == "cleanfiles" ]; then
    echo "Removing trailing whitespace from /srv/http/app/templates/* files"
    FILES="/srv/http/app/templates/*"
    for f in $FILES
    do
        if [ -d "$f" ] ; then
            continue # its a directory not a file
        fi
        numstrpace=$(grep -c '[[:blank:]]$' "$f")
        if [ "$numstrpace" == "0" ] ; then
            continue # no trailing whitespace in the file
        fi
        echo "Trailing whitespace php: $f"
        sed -i 's/[ \t]*$//' "$f"
    done
fi
set -x # echo all commands to cli
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
chmod 755 /srv/http/db/*
# chmod 755 /srv/http/db/redis_datastore_setup
# chmod 755 /srv/http/db/redis_acards_details
chmod 755 /srv/http/app/config/config.php
chmod 755 /etc/X11/xinit/start_chromium.sh
chown mpd.audio /mnt/MPD/*
chown mpd.audio /mnt/MPD/USB/*
chmod 777 /mnt/MPD/USB
chmod 777 /mnt/MPD/USB/*
chown -R mpd.audio /var/lib/mpd
#
# Remove dos2unix if requested
#
if [ "$1" == "final" ] || [ "$2" == "final" ]; then
    echo "Removing dos2unix package"
    pacman -Rs dos2unix --noconfirm
fi
#---
#End script
