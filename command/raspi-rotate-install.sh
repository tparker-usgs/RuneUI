#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#raspi-rotate install script
#
mkdir /home/raspi-rotate
cd /home/raspi-rotate
#
# save the current versions of the distribution files
cp /var/www/app/config/defaults/99-raspi-rotate.conf.* /home/raspi-rotate/
cp /var/www/app/config/defaults/runeaudio-0.5-bootsplashes.tar.gz /home/raspi-rotate/runeaudio-0.5-bootsplashes.tar.gz
cp /var/www/command/01-bootsplash.sh /home/raspi-rotate/01-bootsplash.sh
cp /var/www/command/raspi-rotate-screen.sh /home/raspi-rotate/raspi-rotate-screen.sh
#
# download the reference version
wget https://github.com/gearhead/RuneOS/raw/master/packages/raspi-rotate-rune/raspi-rotate-rune.tar
#
# restore the reference version, incl. documentation copyright, etc.
tar -xpf raspi-rotate-rune.tar -C /
#
# set the file attributes of the documentation files
chmod  755 /usr/share/doc/raspi-rotate
chmod  644 /usr/share/doc/raspi-rotate/*
#
# copy the current versions of the distribution back to their normal places
cp /home/raspi-rotate/99-raspi-rotate.conf.* /var/www/app/config/defaults/
cp /home/raspi-rotate/runeaudio-0.5-bootsplashes.tar.gz /var/www/app/config/defaults/runeaudio-0.5-bootsplashes.tar.gz
cp /home/raspi-rotate/01-bootsplash.sh /var/www/command/01-bootsplash.sh
cp /home/raspi-rotate/raspi-rotate-screen.sh /var/www/command/raspi-rotate-screen.sh
#
# restore the bootsplashes for all screen orientations
tar -xpf /srv/http/app/config/defaults/runeaudio-0.5-bootsplashes.tar.gz -C /
#
# clean up the home directory
cd /home
rm -r /home/raspi-rotate
#
# set the file attributes of the bootsplashes
find /usr/share/boot* -type d -exec chown root.root {} \;
find /usr/share/boot*/*.png -type f -exec chown root.root {} \;
find /usr/share/boot* -type d -exec chmod 755 {} \;
find /usr/share/boot*/*.png -type f -exec chmod 644 {} \;
#
# save a copy of the original bootsplash file set
mv -n /usr/share/bootsplash /usr/share/bootsplash-save
#
# set the screen orientation
/srv/http/command/01-bootsplash.sh NORMAL
#
# copy the config file
cp /var/www/app/config/defaults/99-raspi-rotate.conf.tmpl /usr/share/X11/xorg.conf.d/99-raspi-rotate.conf
