#!/bin/bash

#
## $Id$ ##
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Test si le disque de sauvegarde est mont&#233;"
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

if [ "$BPCMEDIA" = "1" ]; then
##### Teste si le disque USB est mont&#233; #####

mount |grep "\/var\/lib\/backuppc" >/dev/null || MBPC=0
if [ "$MBPC" = "0" ]; then
	echo "Disque USB non mont&#233;"
	EXIT1=1
fi
touch /var/lib/backuppc/test 2&> /dev/null || TBPC=0
if [ "$TBPC" = "0" ]; then
	echo "Impossible d'&#233;crire sur le disque USB"
	EXIT1=1
fi
fi

if [ "$BPCMEDIA" = "3" ]; then

##### Test si le disque NAS est mont&#233; #####

NAS_mntsuffix=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_mntsuffix'"`
NAS_proto=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_proto'"`
mount |grep "\/var\/lib\/backuppc" | grep "$NAS_proto" >/dev/null || MBPC=0
if [ "$MBPC" = "0" ]; then
	echo "Disque NAS non mont&#233;"
	EXIT1=1
fi
touch /var/lib/backuppc/$NAS_mntsuffix/test 2&> /dev/null || TBPC=0
if [ "$TBPC" = "0" ]; then
	echo "Impossible d'&#233;crire sur le disque NAS"
	EXIT1=1
fi
fi

if [ "$EXIT1" = 1 ]; then
	exit 1
fi
