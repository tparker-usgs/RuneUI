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
    $serverHome = $_SERVER['HOME'];
} else {
    $serverHome = '/var/www';
}
// common include
require($serverHome.'/app/config/config.php');
ini_set('display_errors', -1);
error_reporting('E_ALL');
// check current player backend
$activePlayer = $redis->get('activePlayer');
if (isset($_GET['cmd']) && !empty($_GET['cmd'])) {
    switch ($_GET['cmd']) {
        case 'browse':
            if (isset($_POST['path']) && $_POST['path'] !== '') {
                if ($_POST['path'] === 'Albums' OR $_POST['path'] === 'Artists' OR $_POST['path'] === 'Genres' OR $_POST['path'] === 'Composer') {
                    echo json_encode(browseDB($mpd, $_POST['browsemode']));
                } else {
                    echo json_encode(browseDB($mpd, $_POST['browsemode'], $_POST['path']));
                }
            } else {
                if ($activePlayer === 'MPD') {
                    // MPD
                    echo json_encode(browseDB($mpd, $_POST['browsemode']));
                } elseif ($activePlayer === 'Spotify') {
                    // SPOP
                    echo json_encode('home');
                }
            }
            break;
        case 'playlist':
            // open non blocking socket with mpd daemon
            // $mpd2 = openMpdSocket('/run/mpd/socket', 2);
            // getPlayQueue($mpd2);
            // closeMpdSocket($mpd2);
            if ($activePlayer === 'MPD') {
                // $resp = trim(getPlayQueue($mpd), "\x7f..\xff\x0..\x1f");
                // if (substr($resp, 0, 2) == '\n') {
                    // $resp = substr($resp, 2);
                // }
                // echo $resp;
                echo getPlayQueue($mpd);
                // echo trim(getPlayQueue($mpd), "\x7f..\xff\x0..\x1f");
            } elseif ($activePlayer === 'Spotify') {
                echo getSpopQueue($spop);
            }
            break;
        case 'add':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addToQueue($mpd, $_POST['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'addplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    $status = _parseStatusResponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addToQueue($mpd, $_POST['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'addreplaceplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addToQueue($mpd, $_POST['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'lastfmaddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    sendMpdCommand($mpd, 'clear');
                    addToQueue($mpd, $_POST['path']);
                    sendMpdCommand($mpd, 'play');
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $_POST['path']));
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
                if (isset($_POST['path'])) {
                    sendMpdCommand($mpd, "update \"".html_entity_decode($_POST['path'])."\"");
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'MPD update DB path:', 'text' => $_POST['path']));
                }
            }
            break;
        case 'rescan':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    sendMpdCommand($mpd, "rescan \"".html_entity_decode($_POST['path'])."\"");
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'MPD rescan DB path:', 'text' => $_POST['path']));
                }
            }
            break;
        case 'search':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['query']) && isset($_GET['querytype'])) {
                    echo json_encode(searchDB($mpd, $_GET['querytype'], $_POST['query']));
                }
            }
            break;
        case 'bookmark':
            if (isset($_POST['path'])) {
                if (saveBookmark($redis, $_POST['path'])) {
                    ui_notify('Bookmark saved', $_POST['path'].' added to bookmarks');
                    ui_libraryHome($redis);
                } else {
                    ui_notify('Error saving bookmark', 'please try again later');
                }
            }
            if (isset($_POST['id'])) {
                if (deleteBookmark($redis,$_POST['id'])) {
                    ui_notify('Bookmark deleted', '"' . $_POST['name'] . '" successfully removed');
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
                if (isset($_POST['querytype'])) {
                    // if ($_POST['querytype'] === 'amountStation') {
                    if ($_POST['querytype'] === 'amountStation') {
                        //$dirble = json_decode(curlGet($dirblecfg['baseurl'].'amountStation/apikey/'.$dirblecfg['apikey'], $proxy));
                        //echo $dirble->amount;
                        echo '4048'; // Just a fake value, we need a new implementation of this call in v2 api.
                    }
                    // Get primaryCategories
                    if ($_POST['querytype'] === 'categories' OR $_POST['querytype'] === 'primaryCategories' ) {
                        echo curlGet($dirblecfg['baseurl'].'/categories/primary'.$token, $proxy);
                    }
                    // Get childCategories by primaryid
                    if ($_POST['querytype'] === 'childs' && isset($_POST['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/category/'.$_POST['args'].'/childs'.$token, $proxy);
                    }
                    // Get childStations by primaryid
                    if ($_POST['querytype'] === 'childs-stations' && isset($_POST['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/category/'.$_POST['args'].'/stations'.$token, $proxy);
                    }
                    // Get stations by primaryid
                    if ($_POST['querytype'] === 'stations' && isset($_POST['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/category/'.$_POST['args'].'/stations'.$token, $proxy);
                    }
                    // Get station by ID
                    if ($_POST['querytype'] === 'station' && isset($_POST['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/station/'.$_POST['args'].$token, $proxy);
                    }
                    // Search radio station
                    if ($_POST['querytype'] === 'search' && isset($_POST['args'])) {
                        echo curlGet($dirblecfg['baseurl'].'/search/'.urlencode($_POST['args']).$token, $proxy);
                    }
                    // Get stations by continent
                    //if ($_POST['querytype'] === 'continent' && isset($_POST['args'])) {
                    //    echo curlGet($dirblecfg['baseurl'].'continent/apikey'.$dirblecfg['apikey'].'/continent/'.$_POST['args'], $proxy);
                    //}
                    // Get stations by country
                    //if ($_POST['querytype'] === 'country' && isset($_POST['args'])) {
                    //    echo curlGet($dirblecfg['baseurl'].'country/apikey'.$dirblecfg['apikey'].'/country/'.$_POST['args'], $proxy);
                    //}
                    // Add station
                    //if ($_POST['querytype'] === 'addstation' && isset($_POST['args'])) {
                        // input array $_POST['args'] = array('name' => 'value', 'streamurl' => 'value', 'website' => 'value', 'country' => 'value', 'directory' => 'value')
                    //    echo curlPost($dirblecfg['baseurl'].'station/apikey/'.$dirblecfg['apikey'], $_POST['args'], $proxy);
                    //}
                }
            }
            break;
        case 'jamendo':
            if ($activePlayer === 'MPD') {
                $apikey = $redis->hGet('jamendo', 'clientid');
                $proxy = $redis->hGetall('proxy');
                if ($_POST['querytype'] === 'radio') {
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
                if ($_POST['querytype'] === 'radio' && !empty($_POST['args'])) {
                    echo curlGet('http://api.jamendo.com/v3.0/radios/stream?client_id='.$apikey.'&format=json&name='.$_POST['args'], $proxy);
                }
            }
            break;
        case 'spotify':
            if ($activePlayer === 'Spotify') {
                if (isset($_POST['plid'])) {
                    echo spopDB($spop, $_POST['plid']);
                } else {
                    echo spopDB($spop);
                }
            }
            break;
        case 'spadd':
            if ($activePlayer === 'Spotify') {
                if ($_POST['querytype'] === 'spotify-playlist') {
                    sendSpopCommand($spop, 'add '.$_POST['path']);
                } else {
                    $path = explode('-', $_POST['path']);
                    sendSpopCommand($spop, 'add '.$path[0].' '.$path[1]);
                }
                $redis->hSet('spotify', 'lastcmd', 'add');
                $redis->hIncrBy('spotify', 'plversion', 1);
            }
            break;
        case 'spaddplay':
            if ($activePlayer === 'Spotify') {
                $status = _parseSpopStatusResponse(SpopStatus($spop));
                $trackid = $status['playlistlength'] + 1;
                if ($_POST['querytype'] === 'spotify-playlist') {
                    sendSpopCommand($spop, 'add '.$_POST['path']);
                } else {
                    $path = explode('-', $_POST['path']);
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
                if ($_POST['querytype'] === 'spotify-playlist') {
                    sendSpopCommand($spop, 'add '.$_POST['path']);
                } else {
                    $path = explode('-', $_POST['path']);
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
            // input array= $_POST['radio']['label'] $_POST['radio']['url']
                wrk_control($redis, 'newjob', $data = array('wrkcmd' => 'webradio', 'action' => 'add', 'args' => $_POST['radio']));
            }
            break;
        case 'editradio':
            if ($activePlayer === 'MPD') {
                // input array= $_POST['radio']['label'] $_POST['radio']['newlabel'] $_POST['radio']['url']
                wrk_control($redis, 'newjob', $data = array('wrkcmd' => 'webradio', 'action' => 'edit', 'args' => $_POST['radio']));
            }
            break;
        case 'readradio':
            if ($activePlayer === 'MPD') {
                $name = parseFileStr(parseFileStr($_POST['filename'], '.', 1), '/');
                echo json_encode(array('name' => $name, 'url' => $redis->hGet('webradios', $name)));
            }
            break;
        case 'deleteradio':
            if ($activePlayer === 'MPD') {
                // input array= $_POST['radio']['label']
                wrk_control($redis, 'newjob', $data = array('wrkcmd' => 'webradio', 'action' => 'delete', 'args' => $_POST['radio']));
            }
            break;
        case 'test':
            $proxy = $redis->hGetall('proxy');
            print_r($proxy);
            break;
        case 'albumadd':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addAlbumToQueue($mpd, $_POST['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'albumaddplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    $status = _parseStatusResponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addAlbumToQueue($mpd, $_POST['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'albumaddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addAlbumToQueue($mpd, $_POST['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'artistadd':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addArtistToQueue($mpd, $_POST['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'artistaddplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    $status = _parseStatusResponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addArtistToQueue($mpd, $_POST['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'artistaddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addArtistToQueue($mpd, $_POST['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'genreadd':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addGenreToQueue($mpd, $_POST['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'genreaddplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    $status = _parseStatusResponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addGenreToQueue($mpd, $_POST['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'genreaddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addGenreToQueue($mpd, $_POST['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'composeradd':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addComposerToQueue($mpd, $_POST['path']);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'composeraddplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    $status = _parseStatusResponse($redis, MpdStatus($mpd));
                    $pos = $status['playlistlength'] ;
                    addComposerToQueue($mpd, $_POST['path'], 1, $pos);
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'composeraddreplaceplay':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['path'])) {
                    addComposerToQueue($mpd, $_POST['path'], 1, 0, 1); // last argument is for the "clear" command
                    // send MPD response to UI
                    ui_mpd_response($mpd, array('title' => 'Queue cleared<br> Added to queue', 'text' => $_POST['path']));
                }
            }
            break;
        case 'pl-ashuffle':
            if ($activePlayer === 'MPD') {
                if (isset($_POST['playlist'])) {
                    $jobID = wrk_control($redis, 'newjob', $data = array('wrkcmd' => 'pl_ashuffle', 'args' => $_POST['playlist']));
                    waitSyWrk($redis, $jobID);
                    ui_notify('Started Random Play from the playlist', $_POST['playlist']);
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
