#!/bin/bash

#
## $Id$ ##
#
##### Met la machine � l'heure � partir du serveur de temps indiqu� dans la base MySQL #####
#


if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Met la machine � l'heure � partir du serveur de temps indiqu� dans la base MySQL"
	echo "Usage : aucune option"
	exit
fi	


. /etc/se3/config_c.cache.sh

[ -z "$ntpserv" ] && ntpserv="ntp.midway.ovh"

/usr/sbin/ntpdate -u -s -b $ntpserv
