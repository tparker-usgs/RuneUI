#!/usr/bin/php
<?php
$file = $_FILES["filebackup"];
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
 * along with RuneAudio; see the file COPYING. If not, see
 * <http://www.gnu.org/licenses/gpl-3.0.txt>.
 *
 *  file: /command/restore.php
 *  version: 1.3
 *  coder: janui
 *  date: October 2020
 *
 */
// common include
} else {
    echo "Restore starting - automatic reboot will follow";
}

$restore = exec("sudo /srv/http/command/restore.sh $filedest; echo $?");

if ($restore == 0) {
    // this will never be displayed reboot initiated from restore.sh
    echo "Restored successfully. Reboot recommended";
} else {
    // this will be displayed if an error occurs
    echo "Restore failed !";
}

