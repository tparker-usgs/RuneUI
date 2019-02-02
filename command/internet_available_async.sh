#!/bin/bash
#
# internet_available_async
# ------------------------
# Test to see if an internet connection is available. Disable the artistinfo & lyrics functions when no internet is available
# This will resolve UI freezing problems
wget --force-html --spider --timeout=10 --tries=2 https://www.google.com/ > /dev/null 2>&1
if [ $? -eq 0 ]; then
  echo success
else
  echo failed
fi
#---
#End script
