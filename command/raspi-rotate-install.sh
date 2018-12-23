#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#raspi-rotate install script

cd /home
cp /var/www/app/config/defaults/99-raspi-rotate.conf.tmpl /home/99-raspi-rotate.conf.tmpl
cp /var/www/app/config/defaults/runeaudio-0.5-bootsplashes.tar.gz /home/runeaudio-0.5-bootsplashes.tar.gz
cp /var/www/command/01-bootsplash.sh /home/01-bootsplash.sh
cp /var/www/command/raspi-rotate-screen.sh /home/raspi-rotate-screen.sh
cp /var/www/command/raspi-rotate-install.sh /home/raspi-rotate-install.sh
wget https://github.com/gearhead/RuneOS/raw/master/packages/raspi-rotate-rune/raspi-rotate-rune.tar
tar -xpf raspi-rotate-rune.tar -C /
chmod  755 /usr/share/doc/raspi-rotate
chmod  644 /usr/share/doc/raspi-rotate/*
cp /home/99-raspi-rotate.conf.tmpl /var/www/app/config/defaults/99-raspi-rotate.conf.tmpl
cp /home/runeaudio-0.5-bootsplashes.tar.gz /var/www/app/config/defaults/runeaudio-0.5-bootsplashes.tar.gz
cp /home/01-bootsplash.sh /var/www/command/01-bootsplash.sh
cp /home/raspi-rotate-screen.sh /var/www/command/raspi-rotate-screen.sh
cp /home/raspi-rotate-install.sh /var/www/command/raspi-rotate-install.sh
rm /home/*
tar -xpf --overwrite /srv/http/app/config/defaults/runeaudio-0.5-bootsplashes.tar.gz -C /
find /usr/share/boot* -type d -exec chown root.root {} \;
find /usr/share/boot*/*.png -type f -exec chown root.root {} \;
find /usr/share/boot* -type d -exec chmod 755 {} \;
find /usr/share/boot*/*.png -type f -exec chmod 644 {} \;
mv /usr/share/bootspash /usr/share/bootspash-save
/srv/http/command/01-bootsplash.sh NORMAL
