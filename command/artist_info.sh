#!/bin/bash

artist_name=`mpc -f %artist% | head -n 1`
artist=`perl -MURI::Escape -e 'print uri_escape($ARGV[0]);' "$artist_name"`

echo $artist

curl -s "http://ws.audioscrobbler.com/2.0/?method=artist.getinfo&autocorrect=1&artist=$artist_name&api_key=ba8ad00468a50732a3860832eaed0882&format=json" |sed ':a;N;$!ba;s/\n/<\/br>/g'