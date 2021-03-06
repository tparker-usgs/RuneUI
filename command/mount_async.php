#!/usr/bin/php
<?php
/*
 * Copyright (C) 2013-2015 RuneAudio Team
 * http://www.runeaudio.com
 *
 * RuneUI
 * copyright (C) 2013-2015 - Andrea Coiutti (aka ACX) & Simone De Gregori (aka Orion)
 *
 * RuneOS
 * copyright (C) 2013-2015 - Simone De Gregori (aka Orion) & Carmelo San Giovanni (aka Um3ggh1U)
 *
 * RuneAudio website and logo
 * copyright (C) 2013-2015 - ACX webdesign (Andrea Coiutti)
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
 *  file: command/mount_async.php
 *  version: 0.5
 *  coder: janui
 *  date: 07-11-2018
 */
// common include
ini_set('error_log', '/var/log/runeaudio/mount_async.log');
define('APP', '/srv/http/app/');
include('/srv/http/app/libs/runeaudio.php');
// Connect to Redis backend
$redis = new Redis();
$redis->connect('/run/redis.sock');

// reset logfile
sysCmd('echo "--------------- start: mount_async.php ---------------" > /var/log/runeaudio/mount_async.log');
runelog('WORKER mount_async.php STARTING...');

$allmounted = $redis->get('allmounted');
if (!$allmounted) {
	// parameters: wrk_sourcemount($redis, $action, $id = null, $quiet = false, $quick = false)
	$allmounted = wrk_sourcemount($redis, 'mountall', null, true, false);
	$redis->set('allmounted', $allmounted);
}
