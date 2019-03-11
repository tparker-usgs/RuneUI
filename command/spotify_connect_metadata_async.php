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
 * along with RuneAudio; see the file COPYING. If not, see
 * <http://www.gnu.org/licenses/gpl-3.0.txt>.
 *
 *  file: command/spotify_connect_metadata_async.php
 *  version: 0.5b
 *  coder: janui
 *  date: 20-02-2019
 *
 */
// common include
ini_set('display_errors', '1');
ini_set('error_reporting', -1);
ini_set('error_log', '/var/log/runeaudio/spotify_connect_metadata_async.log');
include('/var/www/app/libs/runeaudio.php');
error_reporting(E_ALL & ~E_NOTICE);

// reset logfile
sysCmd('echo "--------------- start: spotify_connect_metadata_async.php ---------------" > /var/log/runeaudio/spotify_connect_metadata_async.log');
runelog('spotify_connect_metadata_async START');

// delete any existing cover art
sysCmd('rm -f /srv/http/tmp/spotifyd/spotify-connect-cover.*');
// initialise the status array
$status = array();
$status['audio'] = "44100:16:2";
$status['audio_sample_rate'] = "44.1";
$status['audio_sample_depth'] = "16";
$status['bitrate'] = "1411";
$status['audio_channels'] = "Stereo";
$status['random'] = "0";
$status['single'] = "0";
$status['consume'] = "0";
$status['playlist'] = "1";
$status['playlistlength'] = "1";
$status['state'] = "play";
$status['time'] = "0";
$status['elapsed'] = "0";
$status['song_percent'] = "0";
$status['currentartist'] = "Airplay";
$status['currentalbum'] = "-----";
$status['currentsong'] = "-----";
$status['actPlayer'] = "Airplay";
$status['radioname'] = null;
$status['OK'] = null;

// read the parameter - this is the TRACK_ID
$track_id = trim($argv[1]); // TRACK_ID
runelog('spotify_connect_metadata_async TRACK_ID:', $track_id);
// get the metadata
// get the track information
if ($track_id == '') {
	runelog('spotify_connect_metadata_async TRACK_ID:', 'Empty - Terminating');
	return 0;
}
// curl -s https://open.spotify.com/track/<TRACK_ID> | sed 's/<meta/\n<meta/g' | grep -i -E 'og:title|og:image|music:duration|music:album|music:musician'
$command = 'curl -s -f --connect-timeout 5 -m 10 --retry 2 https://open.spotify.com/track/'.$track_id.' | sed '."'".'s/<meta/\n<meta/g'."'".' | grep -i -E '."'".'og:title|og:image|music:duration|music:album|music:musician'."'";
runelog('spotify_connect_metadata_async track:', $command);
$retval = sysCmd($command);
foreach ($retval as &$line) {
	// replace all combinations of single or multiple tab, space, <cr> or <lf> with a single space
	$line = preg_replace('/[\t\n\r\s]+/', ' ', $line);
	// then strip the html out of the response
	$line = str_replace('<meta property="', '', $line);
	$line = str_replace('" content', '', $line);
	$line = str_replace('">', '', $line);
	$line = str_replace('"', '', $line);
	$line = trim($line);
	// result is <identifier>=<value>
	$lineparts = explode('=', $line);
	if ($lineparts[0] === 'og:title') {
		$title = trim($lineparts[1]);
	} elseif ($lineparts[0] === 'og:image') {
		$albumart_url = trim($lineparts[1]);
	} elseif ($lineparts[0] === 'music:duration') {
		$duration_in_sec = trim($lineparts[1]);
	} elseif ($lineparts[0] === 'music:album') {
		$album_url = trim($lineparts[1]);
	} elseif ($lineparts[0] === 'music:musician') {
		$artist_url = trim($lineparts[1]);
	}
	unset($lineparts);
}
unset($retval);
// get the album name
runelog('spotify_connect_metadata_async ALBUM_URL:', $album_url);
if ($album_url == '') {
	runelog('spotify_connect_metadata_async ALBUM_URL:', 'Empty - Terminating');
	return 0;
}
// curl -s <ALBUM_URL> | sed 's/<meta/\n<meta/g' | grep -i 'og:title'
$command = 'curl -s -f --connect-timeout 5 -m 10 --retry 2 '.$album_url.' | sed '."'".'s/<meta/\n<meta/g'."'".' | grep -i '."'".'og:title'."'";
runelog('spotify_connect_metadata_async album:', $command);
$retval = sysCmd($command);
foreach ($retval as &$line) {
	// replace all combinations of single or multiple tab, space, <cr> or <lf> with a single space
	$line = preg_replace('/[\t\n\r\s]+/', ' ', $line);
	// then strip the html out of the response
	$line = str_replace('<meta property="', '', $line);
	$line = str_replace('" content', '', $line);
	$line = str_replace('">', '', $line);
	$line = str_replace('"', '', $line);
	$line = trim($line);
	// result is <identifier>=<value>
	$lineparts = explode('=', $line);
	if ($lineparts[0] === 'og:title') {
		$album = trim($lineparts[1]);
	}
	unset($lineparts);
}
unset($retval);
// get the artist name
runelog('spotify_connect_metadata_async ARTIST_URL:', $artist_url);
if ($artist_url == '') {
	runelog('spotify_connect_metadata_async ARTIST_URL:', 'Empty - Terminating');
	return 0;
}
// curl -s <ARTIST_URL> | sed 's/<meta/\n<meta/g' | grep -i 'og:title'
$command = 'curl -s -f --connect-timeout 5 -m 10 --retry 2 '.$artist_url.' | sed '."'".'s/<meta/\n<meta/g'."'".' | grep -i '."'".'og:title'."'";
runelog('spotify_connect_metadata_async artist:', $command);
$retval = sysCmd($command);
foreach ($retval as &$line) {
	// replace all combinations of single or multiple tab, space, <cr> or <lf> with a single space
	$line = preg_replace('/[\t\n\r\s]+/', ' ', $line);
	// then strip the html out of the response
	$line = str_replace('<meta property="', '', $line);
	$line = str_replace('" content', '', $line);
	$line = str_replace('">', '', $line);
	$line = str_replace('"', '', $line);
	$line = trim($line);
	// result is <identifier>=<value>
	$lineparts = explode('=', $line);
	if ($lineparts[0] === 'og:title') {
		$artist = trim($lineparts[1]);
	}
	unset($lineparts);
}
unset($retval);
// get the album art file
runelog('spotify_connect_metadata_async ALBUMART_URL:', $albumart_url);
if ($albumart_url == '') {
	runelog('spotify_connect_metadata_async ALBUMART_URL:', 'Empty - Terminating');
	return 0;
}
// wget -nv -F -T 10 -t 2 -O /srv/http/tmp/spotifyd/spotify-connect-cover https://i.scdn.co/image/<ALBUMART_URL>
$command = 'wget -nv -F -T 10 -t 2 -O /srv/http/tmp/spotifyd/spotify-connect-cover '.$albumart_url;
runelog('spotify_connect_metadata_async artist:', $command);
$retval = sysCmd($command);
if (filesize('/srv/http/tmp/spotifyd/spotify-connect-cover') <= 100) {
	runelog('spotify_connect_metadata_async ALBUMART FILE:', 'Empty');
	$redis->hSet('lyrics', 'art', '/srv/http/tmp/spotifyd/spotify-connect-default.png');
	sysCmd('rm -f /srv/http/tmp/spotifyd/spotify-connect-cover.*');
} else {
	// extract the first 32 characters from the album art file
	$fp = fopen('/srv/http/tmp/spotifyd/spotify-connect-cover', 'r');
	$data_32 = fread($fp, 32);
	fclose($fp);
	// determine the album art file type
	if (strpos(' '.$data_32, 'PNG')) {
		$imgtype = 'png';
	} else if (strpos(' '.$data_32, 'JFIF')) {
		$imgtype = 'jpg';
	} else {
		$imgtype = 'jpg';
	}
	rename("/srv/http/tmp/spotifyd/spotify-connect-cover", "/srv/http/tmp/spotifyd/spotify-connect-cover.".$imgtype);
	$redis->hSet('lyrics', 'art', '/srv/http/tmp/spotifyd/spotify-connect-cover.'.$imgtype);
}
unset($retval);
$status['time'] = $duration_in_sec;
$status['currentartist'] = $artist;
$status['currentalbum'] = $album;
$status['currentsong'] = $title;
$redis->hSet('lyrics', 'time', $status['time']);
$redis->hSet('lyrics', 'artist', $status['currentartist']);
$redis->hSet('lyrics', 'currentartist', lyricsStringClean($status['currentartist'], 'artist'));
$redis->hSet('lyrics', 'album', $status['currentalbum']);
$redis->hSet('lyrics', 'currentalbum', lyricsStringClean($status['currentalbum']));
$redis->hSet('lyrics', 'song', $status['currentsong']);
$redis->hSet('lyrics', 'currentsong', lyricsStringClean($status['currentsong']));
// save JSON response for extensions
ui_render('playback', json_encode($status));
sysCmd('curl -s -X GET http://localhost/command/?cmd=renderui');
sysCmdAsync('/var/www/command/ui_update_async');
return 1;
