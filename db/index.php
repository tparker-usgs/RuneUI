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
 *  file: db/index.php
 *  version: 1.3
 *  coder: Simone De Gregori
 *
 */
// Environment vars
// common include
if ((isset($_SERVER['HOME'])) && ($_SERVER['HOME']) && ($_SERVER['HOME'] != '/root')) {
    include($_SERVER['HOME'].'/app/config/config.php');
} else {
    include('/var/www/app/config/config.php');
}
ini_set('display_errors', -1);
error_reporting('E_ALL');
// check current player backend
$activePlayer = $redis->get('activePlayer');

$param = array("cmd" => "", "browsemode" => "", "path" => "", "query" => "", "querytype" => "", "id" => "", "name" => "", "args" => "", "plid" => "", "radio" => "", "filename" => "", "playlist" => "");
// debug
if (!empty($_GET['cmd'])) {
    $file = '/var/log/runeaudio/db_index_'.$_GET['cmd'].'.log';
} else {
    $file = '/var/log/runeaudio/db_index.log';    
}
$fp = fopen($file, 'w');
fwrite($fp, "---start params---");
foreach ($_GET as $key => $value) {
    // fill the param array
    $param[$key] = $value;
    fwrite($fp, "\n---".$key."---\n");
    fwrite($fp, $value);
}
foreach ($_POST as $key => $value) {
    // fill the param array
    $param[$key] = $value;
    fwrite($fp, "\n---".$key."---\n");
    fwrite($fp, $value);
}
fwrite($fp, "\n---end params---\n");
if (!empty($param['cmd'])) {
    switch ($param['cmd']) {
        case 'browse':
            if (!empty($param['path'])) {
                if ($param['path'] === 'Albums' OR $param['path'] === 'Artists' OR $param['path'] === 'Genres' OR $param['path'] === 'Composer') {
                    $resp = json_encode(browseDB($mpd, $param['browsemode']));
                    // debug
                    fwrite($fp, "\n---response---\n");
                    fwrite($fp, $resp);
                    fwrite($fp, "\n---end response---\n");
                    echo $resp;
                } else {
                    $resp = json_encode(browseDB($mpd, $param['browsemode'], $param['path']));
                    // debug
                    fwrite($fp, "\n---response---\n");
                    fwrite($fp, $resp);
                    fwrite($fp, "\n---end response---\n");
                    echo $resp;
                }
            } else {
                if ($activePlayer === 'MPD') {
                    // MPD
                    $resp = json_encode(browseDB($mpd, $param['browsemode']));
                    // debug
                    fwrite($fp, "\n---response---\n");
                    fwrite($fp, $resp);
                    fwrite($fp, "\n---end response---\n");
                    echo $resp;
                } elseif ($activePlayer === 'Spotify') {
                    // SPOP
                    $resp = json_encode('home');
                    // debug
                    fwrite($fp, "\n---response---\n");
                    fwrite($fp, $resp);
                    fwrite($fp, "\n---end response---\n");
                    echo $resp;
                }
            }
            break;
        case 'playlist':
            // open non blocking socket with mpd daemon
            // $mpd2 = openMpdSocket('/run/mpd/socket', 2);
            // getPlayQueue($mpd2);
            // closeMpdSocket($mpd2);
            if ($activePlayer === 'MPD') {
                $resp = trim(getPlayQueue($mpd));
                // debug
                fwrite($fp, "\n---response---\n");
                fwrite($fp, $resp);
                fwrite($fp, "\n---end response---\n");
                echo $resp;
            } elseif ($activePlayer === 'Spotify') {
                echo getSpopQueue($spop);
            }
            break;
        case 'add':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addToQueue($mpd, $param['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'addplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    $status = _parseStatusresponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addToQueue($mpd, $param['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'addreplaceplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addToQueue($mpd, $param['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'lastfmaddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    sendMpdCommand($mpd, 'clear');
                    addToQueue($mpd, $param['path']);
                    sendMpdCommand($mpd, 'play');
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $param['path']));
                    // Get the current track and try to use LastFM to populate a similar playlist
                    $curTrack = getTrackInfo($mpd, $status['song']);
                    if (isset($curTrack[0]['Title'])) {
                        $status['currentartist'] = $curTrack[0]['Artist'];
                        $status['currentsong'] = $curTrack[0]['Title'];
                        $status['currentalbum'] = $curTrack[0]['Album'];
                        $status['fileext'] = parseFileStr($curTrack[0]['file'], '.');
                        $proxy = $redis->hGetall('proxy');
                        $lastfm_apikey = $redis->get('lastfm_apikey');                    
                        ui_lastFM_similar($status['currentartist'], $status['currentsong'], $lastfm_apikey, $proxy);
                    }
                }
            }
            break;
        case 'update':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    sendMpdCommand($mpd, "update \"".html_entity_decode($param['path'])."\"");
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'MPD update DB path:', 'text' => $param['path']));
                }
            }
            break;
        case 'rescan':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    sendMpdCommand($mpd, "rescan \"".html_entity_decode($param['path'])."\"");
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'MPD rescan DB path:', 'text' => $param['path']));
                }
            }
            break;
        case 'search':
            if ($activePlayer === 'MPD') {
                if (isset($param['query']) && isset($param['querytype'])) {
                    echo json_encode(searchDB($mpd, $param['querytype'], $param['query']));
                }
            }
            break;
        case 'bookmark':
            if (!empty($param['path'])) {
                if (saveBookmark($redis, $param['path'])) {
                    ui_notify('Bookmark saved', $param['path'].' added to bookmarks');
                    ui_libraryHome($redis);
                } else {
                    ui_notify('Error saving bookmark', 'please try again later');
                }
            }
            if (isset($param['id'])) {
                if (deleteBookmark($redis,$param['id'])) {
                    ui_notify('Bookmark deleted', '"' . $param['name'] . '" successfully removed');
                    ui_libraryHome($redis);
                } else {
                    ui_notify('Error deleting bookmark', 'Please try again later');
                }
            }
            break;
        case 'dirble':
            if ($activePlayer === 'MPD') {
                $proxy = $redis->hGetall('proxy');
                $dirblecfg = $redis->hGetAll('dirble');
                $token = '?all=1&token='.$dirblecfg['apikey'];
                $dirblecfg['baseurl'] = 'http://api.dirble.com/v2';
                if (isset($param['querytype'])) {
                    // if ($param['querytype'] === 'amountStation') {
                    if ($param['querytype'] === 'amountStation') {
                        //$dirble = json_decode(curlGet($dirblecfg['baseurl'].'amountStation/apikey/'.$dirblecfg['apikey'], $proxy));
                        //echo $dirble->amount;
                        echo '4048'; // Just a fake value, we need a new implementation of this call in v2 api.
                    }
                    // Get primaryCategories
                    if ($param['querytype'] === 'categories' OR $param['querytype'] === 'primaryCategories' ) {
                        echo curlGet($dirblecfg['baseurl'].'/categories/primary'.$token, $proxy);
                    }
                    // Get childCategories by primaryid
                    if ($param['querytype'] === 'childs' && isset($param['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/category/'.$param['args'].'/childs'.$token, $proxy);
                    }
                    // Get childStations by primaryid
                    if ($param['querytype'] === 'childs-stations' && isset($param['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/category/'.$param['args'].'/stations'.$token, $proxy);
                    }
                    // Get stations by primaryid
                    if ($param['querytype'] === 'stations' && isset($param['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/category/'.$param['args'].'/stations'.$token, $proxy);
                    }
                    // Get station by ID
                    if ($param['querytype'] === 'station' && isset($param['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/station/'.$param['args'].$token, $proxy);
                    }
                    // Search radio station
                    if ($param['querytype'] === 'search' && isset($param['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/search/'.urlencode($param['args']).$token, $proxy);
                    }
                    // Get stations by continent
                    //if ($param['querytype'] === 'continent' && isset($param['args'])) {
                    //    echo curlGet($dirblecfg['baseurl'].'continent/apikey'.$dirblecfg['apikey'].'/continent/'.$param['args'], $proxy);
                    //}
                    // Get stations by country
                    //if ($param['querytype'] === 'country' && isset($param['args'])) {
                    //    echo curlGet($dirblecfg['baseurl'].'country/apikey'.$dirblecfg['apikey'].'/country/'.$param['args'], $proxy);
                    //}
                    // Add station
                    //if ($param['querytype'] === 'addstation' && isset($param['args'])) {
                        // input array $param['args'] = array('name' => 'value', 'streamurl' => 'value', 'website' => 'value', 'country' => 'value', 'directory' => 'value')
                    //    echo curlPost($dirblecfg['baseurl'].'station/apikey/'.$dirblecfg['apikey'], $param['args'], $proxy);
                    //}
                }
            }
            break;
        case 'jamendo':
            if ($activePlayer === 'MPD') {
                $apikey = $redis->hGet('jamendo', 'clientid');
                $proxy = $redis->hGetall('proxy');
                if ($param['querytype'] === 'radio') {
                    $jam_channels = json_decode(curlGet('http://api.jamendo.com/v3.0/radios/?client_id='.$apikey.'&format=json&limit=200', $proxy));
                        foreach ($jam_channels->results as $station) {
                            $channel = json_decode(curlGet('http://api.jamendo.com/v3.0/radios/stream?client_id='.$apikey.'&format=json&name='.$station->name, $proxy));
                            $station->stream = $channel->results[0]->stream;
                        }
                    // TODO: add cache jamendo channels on Redis
                    // $redis->hSet('jamendo', 'ch_cache', json_encode($jam_channels));
                    // echo $redis->hGet('jamendo', 'ch_cache');
                    echo json_encode($jam_channels);
                }
                if ($param['querytype'] === 'radio' && !empty($param['args'])) {
                    echo curlGet('http://api.jamendo.com/v3.0/radios/stream?client_id='.$apikey.'&format=json&name='.$param['args'], $proxy);
                }
            }
            break;
        case 'spotify':
            if ($activePlayer === 'Spotify') {
                if (isset($param['plid'])) {
                    echo spopDB($spop, $param['plid']);
                } else {
                    echo spopDB($spop);
                }
            }
            break;
        case 'spadd':
            if ($activePlayer === 'Spotify') {
                if ($param['querytype'] === 'spotify-playlist') {
                    sendSpopCommand($spop, 'add '.$param['path']);
                } else {
                    $path = explode('-', $param['path']);
                    sendSpopCommand($spop, 'add '.$path[0].' '.$path[1]);
                }
                $redis->hSet('spotify', 'lastcmd', 'add');
                $redis->hIncrBy('spotify', 'plversion', 1);
            }
            break;
        case 'spaddplay':
            if ($activePlayer === 'Spotify') {
                $status = _parseSpopStatusresponse(SpopStatus($spop));
                $trackid = $status['playlistlength'] + 1;
                if ($param['querytype'] === 'spotify-playlist') {
                    sendSpopCommand($spop, 'add '.$param['path']);
                } else {
                    $path = explode('-', $param['path']);
                    sendSpopCommand($spop, 'add '.$path[0].' '.$path[1]);
                }
                $redis->hSet('spotify', 'lastcmd', 'add');
                $redis->hIncrBy('spotify', 'plversion', 1);
                usleep(300000);
                sendSpopCommand($spop, 'goto '.$trackid);
            }
            break;
        case 'spaddreplaceplay':
            if ($activePlayer === 'Spotify') {
                sendSpopCommand($spop, 'qclear');
                if ($param['querytype'] === 'spotify-playlist') {
                    sendSpopCommand($spop, 'add '.$param['path']);
                } else {
                    $path = explode('-', $param['path']);
                    sendSpopCommand($spop, 'add '.$path[0].' '.$path[1]);
                }
                $redis->hSet('spotify', 'lastcmd', 'add');
                $redis->hIncrBy('spotify', 'plversion', 1);
                usleep(300000);
                sendSpopCommand($spop, 'play');
            }
            break;
        case 'addradio':
            if ($activePlayer === 'MPD') {
            // input array= $param['radio']['label'] $param['radio']['url']
                wrk_control($redis, 'newjob', $data = array('wrkcmd' => 'webradio', 'action' => 'add', 'args' => $param['radio']));
            }
            break;
        case 'editradio':
            if ($activePlayer === 'MPD') {
                // input array= $param['radio']['label'] $param['radio']['newlabel'] $param['radio']['url']
                wrk_control($redis, 'newjob', $data = array('wrkcmd' => 'webradio', 'action' => 'edit', 'args' => $param['radio']));
            }
            break;
        case 'readradio':
            if ($activePlayer === 'MPD') {
                $name = parseFileStr(parseFileStr($param['filename'], '.', 1), '/');
                echo json_encode(array('name' => $name, 'url' => $redis->hGet('webradios', $name)));
            }
            break;
        case 'deleteradio':
            if ($activePlayer === 'MPD') {
                // input array= $param['radio']['label']
                wrk_control($redis, 'newjob', $data = array('wrkcmd' => 'webradio', 'action' => 'delete', 'args' => $param['radio']));
            }
            break;
        case 'test':
            $proxy = $redis->hGetall('proxy');
            print_r($proxy);
            break;
        case 'albumadd':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addAlbumToQueue($mpd, $param['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'albumaddplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    $status = _parseStatusresponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addAlbumToQueue($mpd, $param['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'albumaddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addAlbumToQueue($mpd, $param['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'artistadd':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addArtistToQueue($mpd, $param['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'artistaddplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    $status = _parseStatusresponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addArtistToQueue($mpd, $param['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'artistaddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addArtistToQueue($mpd, $param['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'genreadd':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addGenreToQueue($mpd, $param['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'genreaddplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    $status = _parseStatusresponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addGenreToQueue($mpd, $param['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'genreaddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addGenreToQueue($mpd, $param['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'composeradd':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addComposerToQueue($mpd, $param['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'composeraddplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    $status = _parseStatusresponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addComposerToQueue($mpd, $param['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'composeraddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (!empty($param['path'])) {
                    addComposerToQueue($mpd, $param['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $param['path']));
                }
            }
            break;
        case 'pl-ashuffle':
            if ($activePlayer === 'MPD') {
                if (isset($param['playlist'])) {
                    $jobID = wrk_control($redis, 'newjob', $data = array('wrkcmd' => 'pl_ashuffle', 'args' => $param['playlist']));
                    waitSyWrk($redis, $jobID);
                    ui_notify('Started Random Play from the playlist', $param['playlist']);
                    sleep(3);
                    ui_notify('', 'To enable Global Random Play, delete the playlist: RandomPlayPlaylist');
                }
            }
            break;
        case 'pl-ashuffle-stop':
            if ($activePlayer === 'MPD') {
                // sysCmdAsync('/usr/bin/killall ashuffle &');
                ui_notify('Use the MPD menu to switch Random Play off', '');
            }
            break;
    }
} else {
  echo 'MPD DB INTERFACE<br>';
  echo 'INTERNAL USE ONLY<br>';
  echo 'hosted on runeaudio.local:81';
}
// close palyer backend connection
if ($activePlayer === 'MPD') {
    // close MPD connection
    closeMpdSocket($mpd);
} elseif ($activePlayer === 'Spotify') {
    // close SPOP connection
    closeSpopSocket($spop);
}
// close Redis connection
$redis->close();
// debug
fclose($fp);
