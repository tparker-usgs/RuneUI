#!/bin/sh
# script to set up chromium and run it
# this forces chromium to use a /tmp folder
# on a tmpfs drive so it does not beat on the sd card
# chromium usea a ton of files.

CHROMIUM_TEMP=/tmp/chromium
sudo http mkdir -p $CHROMIUM_TEMP
export HOME=$CHROMIUM_TEMP

chromium \
--app=http://localhost \
--start-fullscreen \
--force-device-scale-factor=1.8
