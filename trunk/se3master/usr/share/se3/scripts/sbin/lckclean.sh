#!/bin/bash

#
## $Id$ ##
#
##### Supprime les lock dans /home/netlogon #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Supprime les fichiers lock dans netlogon"
	echo "Usage : aucune option"
	exit
fi

date >> /var/log/se3/lck.log
ls -l /home/netlogon/*.lck 2>/dev/null | wc -l >> /var/log/se3/lck.log
rm -f /home/netlogon/*.lck 2>/dev/null
rm -f /tmp/*.lck
rm -f /var/www/se3/wpkg/bin/associer.lock