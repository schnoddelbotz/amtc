#!/bin/sh

# wsman-test.sh - part of amtc
#  for testing/debugging purposes... must be run in amtc src dir
# e.g.
#   ./wsman-test.sh yourhost | grep --color PowerState
#   ./wsman-test.sh yourhost down

if [ -z "$AMT_PASSWORD" ]; then
 echo 'Like with amttool or amtc, set AMT password via environment'
 exit 1
fi

if [ -z "$1" ]; then
 echo "Need AMT hostname as first argument, action as second."
 echo "Actions: up, down, reset - default if none is info"
 exit 2
fi
host=$1

if [ -z "$2" ]; then
  cmd=wsman_info_step2
else
  cmd=wsman_$2
fi

enumContext=""
if [ $cmd = "wsman_info_step2" ]; then
  echo Retreiving enumeration context from $host ...
  enumContext=`curl --silent -X POST --digest --user "admin:$AMT_PASSWORD" \
    --data @wsman_info http://$host:16992/wsman | \
    sed -re 's@.*<g:EnumerationContext>([A-Z0-9-]+).*@\1@' `
  echo Got enumeration context: $enumContext
  echo Requesting info using context...
fi

cat $cmd | sed -e "s@%s@$enumContext@" | \
    curl --silent -X POST --digest --user "admin:$AMT_PASSWORD" \
    --data @- http://$host:16992/wsman  

