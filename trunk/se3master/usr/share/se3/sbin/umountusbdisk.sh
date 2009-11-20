#!/bin/bash

#
## $Id$ ##
#
##### Démonte le disque USB ou NAS #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "D&#233;monte le disque USB/NAS"
	echo "Usage : aucune option"
	exit
fi

if [ -e /var/www/se3/includes/config.inc.php ]
then
        dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "impossible d'acceder aux params mysql"
        exit 1
fi

BPCMEDIA=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='bpcmedia'"`
if [ "$BPCMEDIA" = "3" ]; then
	mntsuffix=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_mntsuffix'"`
else
	$mntsuffix=""
fi

echo "Deconnexion du disque de sauvegarde en cours..."
/etc/init.d/backuppc stop > /dev/null
sleep 2
ps auxw |grep backuppc | grep www-se3 >/dev/null && killall -9 "/usr/bin/perl"
mount |grep "\/var\/lib\/backuppc" >/dev/null || MBPC=0
if [ ! "$MBPC" = "0" ]; then
	umount /var/lib/backuppc/$mntsuffix
fi
mount |grep "\/var\/lib\/backuppc" >/dev/null && EXIT1=1
if [ "$EXIT1" = 1 ]; then
	echo "Impossible de d&#233;monter le disque de sauvegarde" 
	echo "Impossible de d&#233;monter le disque de sauvegarde" | mail -s "Erreur sauvegarde se3" root
	exit 1
fi
