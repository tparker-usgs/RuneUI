#!/bin/bash
#
# internet_available_async
# ------------------------
# Test to determine if an internet connection is available and check all the other services.
# This will allow graceful disabling of Rune service functionality in the UI.
#
# internet
# determine if we can see google.com, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://www.google.com/ > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # internet connection is available
    redis-cli hset service internet 1
    redis-cli hset service webradio 1
else
    # internet connection not available
    redis-cli hset service internet 0
    redis-cli hset service webradio 0
    redis-cli hset service dirble 0
    redis-cli hset service lastfm 0
    redis-cli hset service makeitpersonal 0
    redis-cli hset service musicbrainz 0
    redis-cli hset service coverartarchiveorg 0
    redis-cli hset service wikipedia 0
    redis-cli hset service azlyrics 0
    redis-cli hset service discogs 0
    redis-cli hset service fanarttv 0
    redis-cli hset service jamendo 0
    # just in case something has gone wrong with the local router link, try to reconnect
    # if an ip-address is assigned, use IP to take the nics down and bring them up to remove the ip address 
    # connman will then reconnect automatically
    # only external ip addresses (192.168.x.x)
    # exclude any nic working as an access point (standard is 192.168.5.1)
    ACCESSPOINT=$( redis-cli hget AccessPoint ip-address )
    NICS=$(ip -o -br  address | grep '192.168' | grep -v '$ACCESSPOINT' | cut -d ' ' -f1)
    for NIC in $NICS
    do
        # first use ifconfig to remove the IPv4 address - documentation says it is the best way to do it for a primary address
        ifconfig $NIC 0.0.0.0
        # the preferred way to do it is with ip, first for IPv6
        ip -6 address flush $NIC
        # and for IPv4
        ip -4 address flush $NIC
        # take the nic down
        ip link set dev $NIC down
    done
    # remove the cached connman configuration files
    rm /var/lib/connman/wifi_*/settings
    rm /var/lib/connman/ethernet_*/settings
    for NIC in $NICS
    do
        # bring the nic up
        ip link set dev $NIC up
        # connman now detects a new nic with no IP-address and will now attempt to reconnect
    done
    # finally run refresh nics
    /srv/http/command/refresh_nics
    exit
fi
# dirble
# determine if we can see dirble.com, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://www.dirble.com/ > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # dirble is available
    redis-cli hset service dirble 1
else
#   dirble is not available
    redis-cli hset service dirble 0
fi
# last.fm
# determine if we can see ws.audioscrobbler.com, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://ws.audioscrobbler.com/ > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # last.fm is available
    redis-cli hset service lastfm 1
else
    # last.fm is not available
    redis-cli hset service lastfm 0
fi
# makeitpersonal lyrics
# determine if we can see makeitpersonal.co/, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://makeitpersonal.co/ > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # lyrics is available
    redis-cli hset service makeitpersonal 1
else
    # lyrics is not available
    redis-cli hset service makeitpersonal 0
fi
# musicbrainz
# determine if we can see musicbrainz.org/ws/2/, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://musicbrainz.org/ws/2/ > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # musicbrainz is available
    redis-cli hset service musicbrainz 1
else
    # musicbrainz is not available
    redis-cli hset service musicbrainz 0
fi
# coverartarchive.org
# determine if we can see coverartarchive.org, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://coverartarchive.org > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # coverartarchive.org is available
    redis-cli hset service coverartarchiveorg 1
else
    # coverartarchive.org is not available
    redis-cli hset service coverartarchiveorg 0
fi
# wikipedia
# determine if we can see upload.wikimedia.org, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://upload.wikimedia.org > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # wikipedia is available
    redis-cli hset service wikipedia 1
else
    # wikipedia is not available
    redis-cli hset service wikipedia 0
fi
# azlyrics
# determine if we can see search.azlyrics.com/search.php, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://search.azlyrics.com/search.php > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # azlyrics is available
    redis-cli hset service azlyrics 1
else
    # azlyrics is not available
    redis-cli hset service azlyrics 0
fi
# discogs
# determine if we can see www.discogs.com/search, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://www.discogs.com/search > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # discogs is available
    redis-cli hset service discogs 1
else
    # discogs is not available
    redis-cli hset service discogs 0
fi
# fanart.tv
# determine if we can see webservice.fanart.tv/v3/audio, this command will give up after +/-20 seconds (= timeout x retries)
wget --force-html --spider --connect-timeout=1 --timeout=10 --tries=2 https://webservice.fanart.tv > /dev/null 2>&1
if [ $? -eq 0 ]; then
    # fanart.tv is available
    redis-cli hset service fanarttv 1
else
    # fanart.tv is not available
    redis-cli hset service fanarttv 0
fi
# jamendo
# determine if the warning message on the jamendo website has been removed, currently the stream links provided do not work
count=$( curl -s -f --connect-timeout 1 -m 10 --retry 2 "https://developer.jamendo.com/v3.0/radios/stream" | grep -c "WARNING: actually the stream link returned doesn't work" )
if [ $count -eq 0 ]; then
    # the warning has gone so assume that jamendo is available
    redis-cli hset service jamendo 1
else
    # the warning is still there, jamendo is not available
    redis-cli hset service jamendo 0
fi
#---
#End script
