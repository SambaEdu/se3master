#!/bin/bash



# $Id$ #

##### Configure rsync pour une sauvegarde via Backuppc depuis un autre serveur #####

if [ "$1" = "pass" ]
then
	PASS=`cat /etc/rsyncd.secret | cut -d':' -f2`
	echo "$PASS"
	exit 0
fi	


if [ -e "/tmp/rsyncd.conf" -a "$1" != "" ]
then
	mv /tmp/rsyncd.conf /etc/rsyncd.conf 2>/dev/null
	echo "$2:$3" > /etc/rsyncd.secret
	chmod 400 /etc/rsyncd.secret
	rm -f /tmp/rsyncd.conf 2>/dev/null
fi
	
if [ "$1" = "start" ]
then
echo "
RSYNC_ENABLE=true
RSYNC_CONFIG_FILE=/etc/rsyncd.conf
RSYNC_OPTS='' " > /etc/default/rsync

/etc/init.d/rsync start 2>/dev/null

fi

if [ "$1" = "stop" ]
then
echo "
RSYNC_ENABLE=false
RSYNC_CONFIG_FILE=/etc/rsyncd.conf
RSYNC_OPTS='' " > /etc/default/rsync

/etc/init.d/rsync stop 2>/dev/null
fi

if [ "$1" = "restart" ]
then
echo "
RSYNC_ENABLE=true
RSYNC_CONFIG_FILE=/etc/rsyncd.conf
RSYNC_OPTS='' " > /etc/default/rsync

/etc/init.d/rsync restart 2>/dev/null
fi

