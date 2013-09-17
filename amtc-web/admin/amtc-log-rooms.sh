#!/bin/sh

# amtc-web.phpsh - part of amtc-web, part of amtc
# https://github.com/schnoddelbotz/amtc
#
# shell wrapper used to call amtc-web for spooling & logging
# it calls amtc-web.php through admin.php to gain admin privs


DIR=$(cd "$(dirname "$0")"; pwd)

AMTCWEB="$DIR/admin.php"

for room in $@; do
  /usr/bin/env php "$AMTCWEB" logState -roomname=$room > /dev/null
done

