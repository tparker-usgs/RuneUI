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

// Connect to Redis backend
$redis = new Redis();
$redis->connect('/run/redis.sock');

// read the parameters - this is the PLAYER_EVENT and TRACK_ID
$event = trim($argv[1]); // PLAYER_EVENT
runelog('spotify_connect_metadata_async PLAYER_EVENT:', $event);
$track_id = trim($argv[2]); // TRACK_ID
runelog('spotify_connect_metadata_async TRACK_ID    :', $track_id);
if ($event == '') {
	runelog('spotify_connect_metadata_async PLAYER_EVENT:', 'Empty - Terminating');
	return 0;
}
if ($track_id == '') {
	runelog('spotify_connect_metadata_async TRACK_ID:', 'Empty - Terminating');
	return 0;
}

// get the metadata
$last_track_id = $redis->hGet('spotifyconnect', 'track_id');
$status = array();
if ($last_track_id == '') {
	// initialise the status array
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
	$status['state'] = "stop";
	$status['time'] = "0";
	$status['elapsed'] = "0";
	$status['song_percent'] = "100";
	$status['currentartist'] = "SpotifyConnect";
	$status['currentalbum'] = "-----";
	$status['currentsong'] = "-----";
	$status['actPlayer'] = "SpotifyConnect";
	$status['radioname'] = null;
	$status['OK'] = null;
	// save JSON response for extensions
	ui_render('playback', json_encode($status));
	sysCmd('curl -s -X GET http://localhost/command/?cmd=renderui');
	sysCmdAsync('/var/www/command/ui_update_async');
} else {
	// get the last stored status
	$status = json_decode($redis->get('act_player_info'));
}
if ($track_id == $last_track_id) {
	// same song as last time
	if ($event == 'stop') {
		$status['state'] = "pause";
	} else if ($event == 'start') {
		$status['state'] = "play";
	}
} else {
	// new song, started from the beginning of the track or change track
	$status['state'] = "play";
	$status['time'] = "0";
	$status['elapsed'] = "0";
	$status['song_percent'] = "0";
	$metadataTimestamp = time();
	// delete any existing cover art
	sysCmd('rm -f /srv/http/tmp/spotify-connect/spotify-connect-cover.*');
	// curl -s https://open.spotify.com/track/<TRACK_ID> | sed 's/<meta/\n<meta/g' | grep -i -E 'og:title|og:image|music:duration|music:album|music:musician'
	$command = 'curl -s -f --connect-timeout 5 -m 10 --retry 2 https://open.spotify.com/track/'.$track_id.' | sed '."'".'s/<meta/\n<meta/g'."'".' | grep -i -E '."'".'og:title|og:image|music:duration|music:album|music:musician'."'";
	runelog('spotify_connect_metadata_async track command:', $command);
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
			runelog('spotify_connect_metadata_async track title:', $title);
		} elseif ($lineparts[0] === 'og:image') {
			$albumart_url = trim($lineparts[1]);
			runelog('spotify_connect_metadata_async track albumart_url:', $albumart_url);
		} elseif ($lineparts[0] === 'music:duration') {
			$duration_in_sec = trim($lineparts[1]);
			runelog('spotify_connect_metadata_async track duration_in_sec:', $duration_in_sec);
		} elseif ($lineparts[0] === 'music:album') {
			$album_url = trim($lineparts[1]);
			runelog('spotify_connect_metadata_async track album_url:', $album_url);
		} elseif ($lineparts[0] === 'music:musician') {
			$artist_url = trim($lineparts[1]);
			runelog('spotify_connect_metadata_async track artist_url:', $artist_url);
		}
		unset($lineparts);
	}
	unset($retval);
	// get the album name
	runelog('spotify_connect_metadata_async ALBUM_URL:', $album_url);
	if ($album_url == '') {
		runelog('spotify_connect_metadata_async ALBUM_URL:', 'Empty');
	} else {
		// curl -s <ALBUM_URL> | sed 's/<meta/\n<meta/g' | grep -i 'og:title'
		$command = 'curl -s -f --connect-timeout 5 -m 10 --retry 2 '.$album_url.' | sed '."'".'s/<meta/\n<meta/g'."'".' | grep -i '."'".'og:title'."'";
		runelog('spotify_connect_metadata_async album command:', $command);
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
				runelog('spotify_connect_metadata_async album title:', $album);
			}
			unset($lineparts);
		}
		unset($retval);
	}
	// get the artist name
	runelog('spotify_connect_metadata_async ARTIST_URL:', $artist_url);
	if ($artist_url == '') {
		runelog('spotify_connect_metadata_async ARTIST_URL:', 'Empty');
	} else {
		// curl -s <ARTIST_URL> | sed 's/<meta/\n<meta/g' | grep -i 'og:title'
		$command = 'curl -s -f --connect-timeout 5 -m 10 --retry 2 '.$artist_url.' | sed '."'".'s/<meta/\n<meta/g'."'".' | grep -i '."'".'og:title'."'";
		runelog('spotify_connect_metadata_async artist command:', $command);
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
				runelog('spotify_connect_metadata_async artist title:', $artist);
			}
			unset($lineparts);
		}
		unset($retval);
	}
	// get the album art file
	runelog('spotify_connect_metadata_async ALBUMART_URL:', $albumart_url);
	if ($albumart_url == '') {
		runelog('spotify_connect_metadata_async ALBUMART_URL:', 'Empty - Terminating');
	} else {
		// wget -nv -F -T 10 -t 2 -O /srv/http/tmp/spotify-connect/spotify-connect-cover https://i.scdn.co/image/<ALBUMART_URL>
		$command = 'wget -nv -F -T 10 -t 2 -O /srv/http/tmp/spotify-connect/spotify-connect-cover '.$albumart_url;
		runelog('spotify_connect_metadata_async album art:', $command);
		$retval = sysCmd($command);
		if (filesize('/srv/http/tmp/spotify-connect/spotify-connect-cover') <= 100) {
			runelog('spotify_connect_metadata_async ALBUMART FILE:', 'Empty');
			$redis->hSet('lyrics', 'art', '/srv/http/tmp/spotify-connect/spotify-connect-default.png');
			sysCmd('rm -f /srv/http/tmp/spotify-connect/spotify-connect-cover.*');
		} else {
			// extract the first 32 characters from the album art file
			$fp = fopen('/srv/http/tmp/spotify-connect/spotify-connect-cover', 'r');
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
			rename("/srv/http/tmp/spotify-connect/spotify-connect-cover", "/srv/http/tmp/spotify-connect/spotify-connect-cover.".$imgtype);
			$redis->hSet('lyrics', 'art', '/srv/http/tmp/spotify-connect/spotify-connect-cover.'.$imgtype);
			runelog('spotify_connect_metadata_async image filename:', '/srv/http/tmp/spotify-connect/spotify-connect-cover.'.$imgtype);
		}
		unset($retval);
	}
	$status['time'] = $duration_in_sec;
	$status['currentartist'] = $artist;
	$status['currentalbum'] = $album;
	$status['currentsong'] = $title;
	// calculate elapsed time and song percentage
	$status['elapsed'] = time() - $metadataTimestamp + 1;
	$status['song_percent'] = $status['elapsed'] / $status['time'] * 100;
}
$redis->hSet('lyrics', 'time', $status['time']);
$redis->hSet('lyrics', 'artist', $status['currentartist']);
$redis->hSet('lyrics', 'currentartist', lyricsStringClean($status['currentartist'], 'artist'));
$redis->hSet('lyrics', 'album', $status['currentalbum']);
$redis->hSet('lyrics', 'currentalbum', lyricsStringClean($status['currentalbum']));
$redis->hSet('lyrics', 'song', $status['currentsong']);
$redis->hSet('lyrics', 'currentsong', lyricsStringClean($status['currentsong']));
$redis->hSet('spotifyconnect', 'track_id', $track_id);
$redis->set('act_player_info', json_encode($status));
// save JSON response for extensions
ui_render('playback', json_encode($status));
sysCmd('curl -s -X GET http://localhost/command/?cmd=renderui');
sysCmdAsync('/var/www/command/ui_update_async');
return 1;
