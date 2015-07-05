#!/bin/bash

## $Id$ ##

#
[ ! -e $1 ] && exit 0
mount |grep "\/var\/lib\/backuppc" >/dev/null
if [ "$?" != "0" ]; then
	/etc/init.d/backuppc stop > /dev/null
	sleep 1
	mount $1 /var/lib/backuppc
	sleep 1
	/etc/init.d/backuppc start > /dev/null
fi
