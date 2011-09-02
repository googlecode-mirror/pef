#!/bin/bash

# /usr/bin/kill_ppp.sh
# echo 'www-data ALL=NOPASSWD: /usr/bin/kill_ppp.sh *' >> /etc/sudoers

local_ip=$1
remote_ip=$2


if [ "$#" -le 1 ]; then
   exit 1
fi

PID=`ps aux | grep $local_ip:$remote_ip | grep pppd | awk '{print $2;}'`
echo $PID
if [ "$PID" = "" ]; then
    exit 0
fi
kill -9 $PID
exit 0