#!/bin/bash

# define tracker
tracker=http://202.55.5.243/announce.php
sendtorrenturl=http://202.55.5.243/newtorrents.php
uploadurl=http://202.55.5.243/upload.php
node=$1
datafile=$2

if [ $# -lt 2 ] ; then
	echo 'Need two argument!!! exit'
	exit
fi

rm -rf /p2p/torrent/$datafile.*
rm -rf /p2p/torrent_temp/$datafile.*
/usr/bin/transmission-create -p -o /p2p/torrent_temp/$datafile.torrent -t $tracker /p2p/temp/$datafile
chmod 755 /p2p/torrent_temp/$datafile.torrent
#mv /p2p/torrent_temp/$datafile.torrent /p2p/torrent/$datafile.torrent
mv /p2p/temp/$datafile /p2p/data/
# upload torrent
/usr/bin/curl -F torrent=@/p2p/torrent_temp/$datafile.torrent -F autoset=enabled $sendtorrenturl
/usr/bin/curl $uploadurl?node=$node&torrent=$datafile.torrent
mv /p2p/torrent_temp/$datafile.torrent /p2p/torrent/$datafile.torrent
sleep 2
rm -rf /p2p/torrent/$datafile.*
