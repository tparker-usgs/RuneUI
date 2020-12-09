#!/usr/bin/bash
sleep 5
case $1 in
 start)
if bluetoothctl info | grep -q "0000110d-0000-1000-8000-00805f9b34fb"
 then
   #bluetoothctl info | grep "Device" | cut -b 8-24 >> /root/bt_device.txt
   mpc stop
   systemctl start bluealsa-aplay
 fi
 ;;
 stop)
 systemctl stop bluealsa-aplay
 ;;
esac

