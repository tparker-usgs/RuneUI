#!/bin/bash
set -x # echo all commands to cli
set +e # continue on errors
#
# Remove duplicate entries from a playlist
# Parameter contains the playlist name (not the filename)
#
# check the parameter
if [ "$1" == "" ] ; then
    echo "No playlist name supplied as parameter 1"
    exit
fi
playlist="$1"
#
# get the location of the playlist
playlistDir=$( redis-cli hget mpdconf playlist_directory | xargs )
lastchar="${playlistDir: -1}"
if [ "$lastchar" == "/" ] ; then
    playlistDir=${playlistDir::-1}
fi
#
# set up the filenames
filenameIn="$playlistDir/$playlist.m3u"
filenameSorted="$playlistDir/$playlist.sorted"
#
# check that the playlist file exists
if [ ! -f "$filenameIn" ] ; then
    echo "Invalid playlist, filename not found: $filenameIn"
    exit
fi
#
# sort the playlist
sort $filenameIn -o $filenameSorted
#
# read sequentially through the sorted playlist file
# detect the duplicate records
lastrec=""
while read rec; do
    if [ "$lastrec" == "$rec" ] ; then
        # duplicate found
        # assume a small number of duplicates, so use sed to delete the lines in the playlist
        # remove only the first occurrence from the playlist file
        # test the sed delimiters before using them, try using a '=', '#', '^', '~' and '|' before giving up
        if [ "$rec" != *"="* ] ; then
            # no '=' character in the playlist record so use it as a delimiter for sed
            sed -i "0,\=$rec={\==d;}" "$filenameIn"
        else
            if [ "$rec" != *"#"* ] ; then
                # no '#' character in the playlist record so use it as a delimiter for sed
                sed -i "0,\#$rec#{\##d;}" "$filenameIn"
            else
                if [ "$rec" != *"^"* ] ; then
                    # no '^' character in the playlist record so use it as a delimiter for sed
                    sed -i "0,\^$rec^{\^^d;}" "$filenameIn"
                else
                    if [ "$rec" != *"~"* ] ; then
                        # no '~' character in the playlist record so use it as a delimiter for sed
                        sed -i "0,\~$rec~{\~~d;}" "$filenameIn"
                    else
                        if [ "$rec" != *"|"* ] ; then
                            # no '|' character in the playlist record so use it as a delimiter for sed
                            sed -i "0,\|$rec|{\||d;}" "$filenameIn"
                        fi
                    fi
                fi
            fi
        fi
    fi
    lastrec=$rec
done < "$filenameSorted"
# delete the sorted playlist file
rm "$filenameSorted"
#---
#End script
