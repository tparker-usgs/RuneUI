#!/usr/bin/php
<?php
// common include
ini_set('display_errors', '1');
// ini_set('error_reporting', -1);
ini_set('error_log', '/var/log/runeaudio/refresh_bt.log');
// Connect to Redis backend
$redis = new Redis();
$redis->connect('/run/redis/socket');
require_once('/srv/http/app/libs/runeaudio.php');

// interesting BT profie UUIDs:
// bluetoothctl info
//        UUID: Serial Port               (00001101-0000-1000-8000-00805f9b34fb)
//        UUID: Headset                   (00001108-0000-1000-8000-00805f9b34fb)
//        UUID: Audio Source              (0000110a-0000-1000-8000-00805f9b34fb)
//        UUID: Audio Sink                (0000110b-0000-1000-8000-00805f9b34fb)
//        UUID: A/V Remote Control Target (0000110c-0000-1000-8000-00805f9b34fb)
//        UUID: Advanced Audio Distribu.. (0000110d-0000-1000-8000-00805f9b34fb)
//        UUID: A/V Remote Control        (0000110e-0000-1000-8000-00805f9b34fb)
//        UUID: Headset AG                (00001112-0000-1000-8000-00805f9b34fb)
//        UUID: Handsfree Audio Gateway   (0000111f-0000-1000-8000-00805f9b34fb)
// we get to this script when  bt device connects/disconnects. We need to see what is connected
// bluetoothctl is useless for this as it only shows some devices.
// need to use btmgt and bluetoothctl to actually query the bt 'bus' to see what
// is connected and see if it is 'new'
sleep (5);
// collect the devices into an array by grabbing the MACs from the command:
// 'btmgmt con' - one device per line first 17 characters is the MAC
// # btmgmt con | cut -d " " -f 1
// F8:1F:32:39:FE:31
// 08:B7:38:11:A6:C2

// then parse each device to determine its capabilities by using
// 'bluetoothctl info MAC' then parse the mac to determine it if is a source or a sink
// as we only care about audio devices...
// bluetoothctl info F8:1F:32:39:FE:31 | grep "0000110a"
//        UUID: Audio Source              (0000110a-0000-1000-8000-00805f9b34fb)

// need to keep track with a redis array of device macs, check when one is added
// only deal with audio devices.

//$knownBT = $redis->Get('BT_interfaces');
// array each element is MAC may be empty or already have a connected desvice sound or not
$knownBT = ["08:B7:38:11:A6:C2"]; // this is my keyboard
print_r($knownBT);
// this generates an array of attached BT devices
$attachedBT = sysCmd('btmgmt con | cut -d " " -f 1');
print_r($attachedBT);
switch ($argv[1]) {// passed from udev rule as parameter
case "start":
// connect
print_r("Checking newly connected BT\n");
foreach($attachedBT as &$value){
      print_r($value);
	  print_r("\n");
      //is it already connected?
      foreach($knownBT as &$value1) {
            if ($value != $value1) {
                // $redis-> add to list knownBT...
                $bt_source=sysCmd('bluetoothctl info', $value, ' | grep "0000110a"');
				print_r($bt_source);
                // test to see if it is an audio source or a sink or not of interest
                if (!empty($bt_source) ) { // we have an Audio Source
				//print_r("New Source\n");
                sysCmd ('mpc stop');
                // since we know the MAC, we can put this into the /etc/default/bluealsa
                // and restart, but it is 00:00:00:00:00:00 so it will play
                // everthing from anything
                sysCmd ('systemctl start bluealsa-aplay');
                // add to the list of attached BTs
                // $redis-> add to list...
                }
                $bt_sink=sysCmd('bluetoothctl info ', $value, ' | grep "0000110a"');
                if (!empty($bt_sink)) { // we have an Audio Sink
				print_r('New Sink');
                // set up as synthetic alsa device for MPD et al
                }
                else {
                // nothing
				print_r("Not Audio\n");
                }
            }
			else {
			print_r("Already Known\n");
			}
        }
    }
    break;;
case "stop":
// disconnect
foreach($attachedBT as &$value){
      //check connected against known. Is it still showing in redis?
      foreach($knownBT as &$value1) {
            if ($value != $value1) {
            //redis-> remove from list...
            }
        }
    }
}
?>