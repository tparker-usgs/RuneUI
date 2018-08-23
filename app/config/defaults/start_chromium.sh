#!/bin/sh


CHROMIUM_TEMP=/tmp/chromium
sudo http mkdir -p $CHROMIUM_TEMP
export HOME=$CHROMIUM_TEMP

<<<<<<< Updated upstream
chromium \
--app=http://localhost \ 
--start-fullscreen \
--force-device-scale-factor=1.8
=======
chromium --app=http://localhost --start-fullscreen --force-device-scale-factor=1.8
>>>>>>> Stashed changes
