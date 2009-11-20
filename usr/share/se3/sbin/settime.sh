#!/bin/bash

#
## $Id$ ##
#
##### Met la machine à l'heure à partir du serveur de temps indiqué dans la base MySQL #####
#


if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Met la machine à l'heure à partir du serveur de temps indiqué dans la base MySQL"
	echo "Usage : aucune option"
	exit
fi	

# Détection de la distrib
if [ -e /etc/redhat-release ]; then
        DISTRIB="RH"
        WWWPATH="/var/www/html"
fi
if [ -e /etc/mandrake-release ]; then
        DISTRIB="MDK"
        WWWPATH="/var/www/html"
fi
if [ -e /etc/debian_version ]; then
        DISTRIB="DEB"
        WWWPATH="/var/www"
fi

# Récupération des paramètres mysql

if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
        dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "Fichier de conf inaccessible" >> $SE3LOG
		echo "settime.sh: Status FAILED" >> $SE3LOG
        exit 1
fi

NTPSERV=`echo "SELECT value FROM params WHERE name='ntpserv'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

/usr/sbin/ntpdate -s -b $NTPSERV
