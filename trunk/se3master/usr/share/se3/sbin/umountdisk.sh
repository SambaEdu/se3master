#!/bin/bash

## $Id$ ##
#
mount |grep "\/var\/lib\/backuppc" >/dev/null
if [ "$?" == "0" ]; then
	/etc/init.d/backuppc stop > /dev/null
	sleep 1
	umount -l /var/lib/backuppc
fi
