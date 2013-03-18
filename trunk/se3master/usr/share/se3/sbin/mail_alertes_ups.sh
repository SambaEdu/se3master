#!/bin/bash
# SambaEdu
#
# $Id$
#
##### Expédie les alertes de NUT#####

WWWPATH="/var/www"
DROITS="se3_is_admin"

if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
        dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible"
        exit 1
fi
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
        echo "Impossible d'accéder au paramètre BASEDN"
        exit 1
fi

RIGHTSRDN=`echo "SELECT value FROM params WHERE name='RightsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$RIGHTSRDN" ]; then
        echo "Impossible d'accéder au paramètre RIGHTSRDN"
        exit 1
fi
		
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PEOPLERDN" ]; then
        echo "Impossible d'accéder au paramètre PEOPLERDN"
        exit 1
fi

ALERTE_ACTIVE=`echo "SELECT ACTIVE FROM alertes WHERE NAME='UPS'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ "$ALERTE_ACTIVE" != "1" ]; then
        exit 1
fi

if [ "$DROITS" != "" ]
then
	ldapsearch  -x -b cn=$DROITS,$RIGHTSRDN,$BASEDN '(objectclass=groupOfNames)' member | grep member | grep -v requesting | while read A
	do
		echo "$A" | cut -d: -f2 | cut -d, -f1 | while read B
			do
	 		MAIL_UPS=`ldapsearch  -x -b $B,$PEOPLERDN,$BASEDN '(objectclass=*)' mail | grep mail | grep -v requesting | cut -d: -f2`
			if [ "$MAIL_UPS" != "" ]
			then
				NOM=`/bin/hostname`
				echo $* | mail -s "[Se3: $NOM] UPS Alerte" $MAIL_UPS
			fi	
			done
	done
fi
