#!/usr/bin/php
<?php
/*
 * Copyright (C) 2013-2014 RuneAudio Team
 * http://www.runeaudio.com
 *
 * RuneUI
 * copyright (C) 2013-2014 - Andrea Coiutti (aka ACX) & Simone De Gregori (aka Orion)
 *
 * RuneOS
 * copyright (C) 2013-2014 - Simone De Gregori (aka Orion) & Carmelo San Giovanni (aka Um3ggh1U)
 *
 * RuneAudio website and logo
 * copyright (C) 2013-2014 - ACX webdesign (Andrea Coiutti)
 *
 * This Program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3, or (at your option)
 * any later version.
 *
 * This Program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with RuneAudio; see the file COPYING.  If not, see
 * <http://www.gnu.org/licenses/gpl-3.0.txt>.
 *
 *  file: command/set_mpd_volume.php
 *  version: 1.5
 *  date: 27 July 2018
 *  coder: janui
 *
 */
// common include
ini_set('error_log', '/var/log/set_mpd_volume.log');
define('APP', '/srv/http/app/');
include('/srv/http/app/libs/runeaudio.php');
// Connect to Redis backend
$redis = new Redis();
$redis->connect('/run/redis.sock');

// reset logfile
sysCmd('echo "--------------- start: set_mpd_volume.php ---------------" > /var/log/runeaudio/set_mpd_volume.log');
runelog('WORKER set_mpd_volume.php STARTING...');

// what we are trying to do is set the mpd volume to preset start volume
// and store a value of the last known mpd volume to be used when switching players
$activePlayer = $redis->get('activePlayer');
if ($activePlayer === 'MPD') {
	// Start MPD (if  not started) in order to set the startup volume (if needed and if set) then kill MPD (if required)
	$retval = sysCmd('systemctl is-active mpd');
	if ($retval[0] === 'active') {
		// do nothing
	} else {
		sysCmd('systemctl start mpd');
	}
	unset($retval);
	$mpdstartvolume = $redis->get('mpd_start_volume');
	// sometimes mpd fails to start correctly (e.g. when the incorrect audio card overlay is specified)
	// then it will never return a volume value so limit the number of retries with a counter
	$retries_volume = 40;
	if ($redis->hGet('mpdconf', 'mixer_type') != 'disabled') {
		if ($mpdstartvolume != -1) {
			do {
				sleep(2);
				// retry until MPD is up and returns the any valid volume value
				$retval = sysCmd('mpc volume | grep "volume:" | cut -d ":" -f 2 | cut -d "%" -f 1');
				$initvolume = trim($retval[0]);
				unset($retval);
				if ($initvolume == $mpdstartvolume) {
					// no need to do anything, volume is correct
					$mpdvolume = $mpdstartvolume;
				} else if ($initvolume == 'n/a') {
					// something wrong, mismatch between redis and mpd 'disabled' values
					$retries_volume = 0;
					// set values so that mpd is not restarted
					$mpdvolume = $mpdstartvolume;
					$initvolume = $mpdstartvolume;
				} else {
					// set the mpd volume, do a soft increase/decrease
					$setvolume = $initvolume-round((($initvolume-$mpdstartvolume)/2), 0, PHP_ROUND_HALF_UP);
					$retval = sysCmd('mpc volume '.$setvolume.' | grep "volume:" | cut -d ":" -f 2 | cut -d "%" -f 1');
					$mpdvolume = trim($retval[0]);
					unset($retval);
				}
			} while (($mpdstartvolume != $mpdvolume) && (--$retries_volume > 0));
		} else {
			do {
				sleep(2);
				// retry until MPD is up and returns the any valid volume value
				$retval = sysCmd('mpc volume | grep "volume:" | cut -d ":" -f 2 | cut -d "%" -f 1');
				$mpdvolume = trim($retval[0]);
				unset($retval);
				if ($mpdvolume == 'n/a') {
					// mpd has returned something but, will not return an actual value
					// default to 100%
					$mpdvolume = 100;
				}
			} while ((!is_numeric($mpdvolume)) && (--$retries_volume > 0));
		}
	} else {
		// mixer_type is disabled so set volume variable to 100%
		$mpdvolume = 100;
	}
	// Save the last known MPD volume
	if ($retries_volume > 0) {
		// correctly set or disabled
		$redis->set('lastmpdvolume', $mpdvolume);
		sysCmd('mpc volume '.$mpdvolume);
	} else if ($mpdstartvolume != -1) {
		// failed to set it, but start volume has a value
		$redis->set('lastmpdvolume', $mpdstartvolume);
		sysCmd('mpc volume '.$mpdstartvolume);
	} else {
		// set it to 40% when we don't have a value
		$redis->set('lastmpdvolume', 40);
		sysCmd('mpc volume 40');
	}
	// restart mpd if required
	$retval = sysCmd('systemctl is-active mpd');
	if ($retval[0] === 'active') {
		// do nothing
	} else {
		sysCmd('systemctl start mpd');
	}
	unset($retval);
}
