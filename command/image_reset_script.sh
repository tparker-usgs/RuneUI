#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
cd /home
#
# Image reset script
if [ "$1" == "full" ];
then
    echo "Running full cleanup and image initialisation for a distribution image"
else
    echo "Running quick image initialisation"
fi
#---
# Before running the script...
# Connect via Wired ethernet, remove all WiFi profiles
# Dismount all NAS and USB sources, clear all NAS information. Unplug all USB decvices
# Reset the image using the following commands, some commands may fail (e.g. local-browser not installed), no problem
#
# clean up any no longer valid mounts
udevil clean
#
# set up services and stop them
# systemctl stops after an erroneous entry, use arrays to run through all entries
declare -a disable_arr=(ashuffle mpd mpdscribble nmb smb smbd nmbd winbindd winbind udevil upmpdcli hostapd shairport-sync local-browser rune_SSM_wrk rune_PL_wrk dhcpcd php-fpm ntpd bluetooth chronyd cronie plymouth-lite-halt plymouth-lite-reboot plymouth-lite-poweroff plymouth-lite-start systemd-resolved)
declare -a enable_arr=(avahi-daemon haveged nginx redis rune_SY_wrk sshd systemd-journald systemd-timesyncd bootsplash dbus iwd connman bluetooth bluealsa bluealsa-aplay)
declare -a stop_arr=(ashuffle mpd spopd nmbd nmb smbd smb winbind winbindd shairport-sync local-browser rune_SSM_wrk rune_PL_wrk rune_SY_wrk upmpdcli bluetooth chronyd systemd-timesyncd cronie udevil connman bluetooth bluealsa bluealsa-aplay)
declare -a mask_arr=(connman-vpn dbus-org.freedesktop.resolve1 systemd-logind systemd-resolved)
declare -a unmask_arr=(systemd-journald)
#
# disable specified services
for i in "${disable_arr[@]}"
do
   systemctl disable "$i"
done
#
# enable specified services
for i in "${enable_arr[@]}"
do
   systemctl enable "$i"
done
# unmask masked services
alreadymasked=$( systemctl list-unit-files --state=masked | grep -i service | cut -f 1 -d " " )
for i in $alreadymasked
do
   systemctl unmask "$i"
done
# mask specified services
for i in "${mask_arr[@]}"
do
   systemctl mask "$i"
done
# unmask specified services
for i in "${mask_arr[@]}"
do
   systemctl unmask "$i"
done
# for a distribution image disable systemd audit to reduce log files. Switch it on for a development image
if [ "$1" == "full" ];
then
    systemctl mask systemd-journald-audit.socket
else
    systemctl unmask systemd-journald-audit.socket
fi
#
# stop specified services
for i in "${stop_arr[@]}"
do
   systemctl stop "$i"
done
# stop twice, rune_SY_wrk will try to restart some services (e.g. ashuffle)
for i in "${stop_arr[@]}"
do
   systemctl stop "$i"
done
#
# make sure xwindows has stopped
export DISPLAY=:0
xset dpms force off
#
# unmount the local an network devices
umount -Rf /mnt/MPD/NAS/*
umount -Rf /mnt/MPD/USB/*
rmdir /mnt/MPD/NAS/*
rmdir /mnt/MPD/USB/*
#
# set up connman
# delete the file/link at /etc/resolv.conf
rm -f /etc/resolv.conf
# link it to connman's dynamically created resolv.conf
ln -s /run/connman/resolv.conf /etc/resolv.conf
#
# install raspi-rotate
/var/www/command/raspi-rotate-install.sh
#
# install spotifyd
/var/www/command/spotifyd-install.sh
#
# remove rerns addons menu (if installed)
systemctl stop addons cronie
systemctl disable addons cronie
rm -f /etc/systemd/system/addons.service
rm -f /etc/sudoers.d/http
rm -f /etc/sudoers.d/http-backup
rm -fr /home/rern
/usr/local/bin/uninstall_addo.sh
rm -f ./install.sh
rm -f /usr/local/bin/uninstall_addo.sh
rm -f /usr/local/bin/uninstall_enha.sh
redis-cli del addons
redis-cli del addo
#
# remove user files and logs
rm -rf /var/log/runeaudio/*
umount /srv/http/tmp
umount /var/log/runeaudio
umount /var/log
rm -f /var/lib/mpd/mpd.db
rm -f /var/lib/mpd/mpdstate
rm -rf /var/log/*
rm -rf /root/.*
rm -rf /var/www/test
rm -rf /mnt/MPD/LocalStorage/*
rm -rf /mnt/MPD/Webradio/*
rm -rf /var/lib/mpd/playlists/*
rm -rf /var/lib/mpd/playlists/RandomPlayPlaylist.m3u
rm -rf /srv/http/tmp
rm -f /etc/sudoers.d/*
rm -rf /home/*
rm -rf /var/lib/bluetooth/*
rm -f /var/lib/connman/*.service
rm -rf /var/lib/connman/ethernet_*
rm -rf /var/lib/connman/wifi_*
rm -rf /var/lib/connman/bluetooth_*
#
# remove mac spoofing scripts
rm /etc/systemd/system/macfix_*.service
rm /etc/systemd/system/multi-user.target.wants/macfix_*.service
#
# keep the old nic name format (e.g. eth0, eth1, wlan0, wlan1, etc.)
# remove this symlink to enable the new 'predictable' format
ln -sf /dev/null /etc/udev/rules.d/80-net-setup-link.rules
#
# redis reset
redis-cli del AccessPoint
redis-cli del airplay
redis-cli del debugdata
redis-cli del dirble
redis-cli del dlna
redis-cli del fix_mac
redis-cli del jamendo
redis-cli del local_browser
redis-cli del lyrics
redis-cli del mpdconf
redis-cli del network_info
redis-cli del network_interfaces
redis-cli del nics
redis-cli del samba
redis-cli del spotify
redis-cli del spotifyconnect
redis-cli del translate_mac_nic
redis-cli del usbmounts
php -f /srv/http/db/redis_datastore_setup reset
redis-cli set playerid ""
redis-cli set hwplatformid ""
#
# update local git and clean up any stashes
rm -f /var/www/command/mpd-watchdog
cd /srv/http/
git config --global core.editor "nano"
git config user.email "any@body.com"
git config user.name "anybody"
git stash
git add .
git stash
git pull --no-edit
git stash
if [ "$1" == "full" ]; then
    stashes=$( git stash list | grep -i stash | cut -f 1 -d ":"  )
    for i in $stashes
    do
       git stash drop "$i"
    done
fi
cd /home
#
# remove any git user-names & email
cd /srv/http/
git config user.name ""
git config user.email ""
cd /home
#
# remove any samba passwords
pdbedit -L | grep -o ^[^:]* | smbpasswd -x
#
# reset root password
echo -e "rune\nrune" | passwd root
#
# reset the service and configuration files to the distribution standard
# the following commands should also be run after a system update or any package updates
rm -f /etc/nginx/nginx.conf
rm -f /etc/samba/*.conf
#rm -f /etc/netctl/*
# copy default settings and services
cp -Rv /srv/http/app/config/defaults/etc/* /etc
cp -Rv /srv/http/app/config/defaults/usr/* /usr
cp -Rv /srv/http/app/config/defaults/var/* /var
# copy a standard config.txt
cp -Rv /srv/http/app/config/defaults/boot/* /boot
# make appropriate links
ln -s /etc/nginx/nginx-prod.conf /etc/nginx/nginx.conf
ln -s /etc/samba/smb-prod.conf /etc/samba/smb.conf
#
# copy a logo for display in BubbleUpnp via upmpdcli
cp /srv/http/assets/img/favicon-64x64.png /usr/share/upmpdcli/runeaudio.png
chgmod 644 /usr/share/upmpdcli/runeaudio.png
#
# modify all standard .service files which specify the wrong PIDFile location
sed -i 's|.*PIDFile=/var/run.*/|PIDFile=/run/|g' /usr/lib/systemd/system/*.service
# sed -i 's|.*PIDFile=/var/run.*/|PIDFile=/run/|g' /usr/lib/systemd/system/nmb.service
# sed -i 's|.*PIDFile=/var/run.*/|PIDFile=/run/|g' /usr/lib/systemd/system/winbind.service
# sed -i 's|.*User=mpd.*|#User=mpd|g' /usr/lib/systemd/system/mpd.service
#
# some fixes for the ply-image binary location (required for 0.5b)
if [ -e /usr/bin/ply-image ]; then
    rm /usr/local/bin/ply-image
else
    cp /usr/local/bin/ply-image /usr/bin/ply-image
    rm /usr/local/bin/ply-image
    chmod 755 /usr/bin/ply-image
fi
#
# make sure that all files are unix format and have the correct ownerships and protections
# the 'final' option also removes the dos2unix package
if [ "$1" == "full" ]; then
    /srv/http/command/convert_dos_files_to_unix_script.sh final
else
    /srv/http/command/convert_dos_files_to_unix_script.sh
fi
#
# for a distribution image remove the pacman history. It makes a lot of space free, but that history is useful when developing
if [ "$1" == "full" ]; then
    # remove pacman history and no longer installed packages from the package database
    pacman -Sc --noconfirm
    # remove ALL files from the package cache
    # pacman -Scc --noconfirm
fi
#
# reset systemd services so that any cached files are replaced by the latest ones
systemctl daemon-reload
#
# reset host information (icon-name, chassis and hostname)
hostnamectl --static --transient --pretty set-icon-name multimedia-player
hostnamectl --static --transient --pretty set-chassis embedded
hostnamectl --static --transient --pretty set-hostname runeaudio
#
# clean up /etc/motd
linuxbuilddate=$( uname -v )
i="0"
while [ $i -lt 5 ]; do
    linuxbuilddate=${linuxbuilddate#*[[:space:]]*}
    osdate=$( date -d "$linuxbuilddate" +%Y%m%d )
    if [ $? -eq 0 ]; then
        i="5"
    else
        i=$[$i+1]
    fi
done
osver=$( uname -r | xargs )
buildversion=$( redis-cli get buildversion | xargs )
patchlevel=$( redis-cli get patchlevel | xargs )
release=$( redis-cli get release | xargs )
archarmver=$( uname -msr | xargs )
cd /srv/http/
gitbranch=$( git branch | xargs )
gitbranch=${gitbranch#*[[:space:]]*}
cd /home
if [ $gitbranch = $release ]; then
    experimental="Beta"
else
    experimental="Experimental Beta"
fi
line1="RuneOs: $experimental V$release-gearhead-$osdate"
line2="RuneUI: $gitbranch $buildversion V$release-$patchlevel"
line3="Hw-env: Raspberry Pi ($archarmver)"
sed -i "s|^RuneOs:.*|$line1|g" /etc/motd
sed -i "s|^RuneUI:.*|$line2|g" /etc/motd
sed -i "s|^Hw-env:.*|$line3|g" /etc/motd
cat /etc/motd
#
# set timezone to -11 hours before GMT - any user adjustment will always go forward
timedatectl set-timezone Pacific/Pago_Pago
redis-cli set timezone "Pacific/Pago_Pago"
#
# shutdown redis and force a write all in-memory keys to disk (purges any cached values)
sync
redis-cli save
redis-cli shutdown save
sync
#
# zero fill the file system if parameter 'full' is selected
# this takes ages to run, but the zipped distribution image will then be very small
if [ "$1" == "full" ]; then
    redis-cli save
    echo "Zero filling the file system"
    # zero fill the file system
    cd /boot
    sync
    cat /dev/zero > zero.file
    sync
    rm zero.file
    sync
    cd /
    cat /dev/zero > zero.file
    sync
    rm zero.file
    sync
    cd /home
fi
#
# shutdown & poweroff
shutdown -P now
#---
#End script
