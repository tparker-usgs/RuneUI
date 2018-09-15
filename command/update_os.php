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
 *  file: /srv/http/command/update_os
 *  version: 1.5
 *  date: August 2018
 *  coder: janui
 *
 */

function updateOS($redis) {
	// first check that the patch variable exists
	if (!$redis->exists('patchlevel')) {
		$redis->set('patchlevel', 0);
	}
    // standard format for the update part - patch levels start at 0 (= no patch)
	// even if an image is reset all patches will be applied sequentially
	// patches should always be repeatable without causing problems
	// when a new image is created the patch level will always be set to zero, the following code should also be reviewed
	if ($redis->get('buildversion') === 'janui-20180805') {
		// only applicable for a specific build
		// final update for this build - move to a new buildversion
		$count = sysCmd("cat /srv/http/db/redis_datastore_setup | grep -c -i 'janui-20180805'");
		if ($count[0] == 0) {
			// the new version of /srv/http/db/redis_datastore_setup has been delivered via a git pull so use it
			// carry out/repeat all previous 'janui-20180805' updates first
			sysCmd('cp /var/www/app/config/defaults/nginx-prod.conf /etc/nginx/nginx-prod.conf');
			sysCmd('rm -f /etc/nginx/nginx.conf');
			sysCmd('ln -s /etc/nginx/nginx-prod.conf /etc/nginx/nginx.conf');
			sysCmd('cp /var/www/app/config/defaults/start_chromium.sh /etc/X11/xinit/start_chromium.sh');
			sysCmd('cp /var/www/app/config/defaults/udevil.service /usr/lib/systemd/system/udevil.service');
			sysCmd('cp /var/www/app/config/defaults/50x.html /etc/nginx/html/50x.html');
			$redis->del('acards');
			sysCmd('php -f /srv/http/db/redis_acards_details');
			// check the variables in new version of redis_datastore_setup
			sysCmd('php -f /srv/http/db/redis_datastore_setup check');
			sysCmd('systemctl disable php-fpm');
			// set file protections and ownership
			wrk_sysAcl();
			// set the patch level to 0 and set the next valid build version
			$redis->set('patchlevel', 0);
			$redis->set('buildversion', 'janui-20180903');
		}
		unset($count);
	}
	if ($redis->get('buildversion') === 'janui-20180903') {
		// only applicable for a specific build
		if ($redis->get('patchlevel') == 0) {
			// 1st update - modify /etc/chrony.conf new version of chrony.conf delivered via git pull
			// disable initstepslew, this is already done due to the iburst parameter on the servers and pool in combination with makestep
			sysCmd("sed -i 's/^initstepslew.*/! initstepslew 30 0.pool.ntp.org 1.pool.ntp.org 2.pool.ntp.org/' /etc/chrony.conf");
			// enable logging of time changes of over 20 seconds
			sysCmd("sed -i 's/^! logchange.*/logchange 20/' /etc/chrony.conf");
			// set the patch level
			$redis->set('patchlevel', 1);
		}
		//
		// template for the update part replace x with the number
		//if ($redis->get('patchlevel') < x) {
			// xth update
			//
			// set the patch level
			//$redis->set('patchlevel', x);
		//}
	}
}
