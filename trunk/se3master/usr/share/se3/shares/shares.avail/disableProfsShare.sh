#!/bin/bash
#shares_Win95: prof
#shares_Win2K: prof
#shares_WinXP: prof
#shares_Vista: prof
#shares_Seven: prof
#action: start
#level: 01

WWWPATH="/var/www"
if [ -e ${WWWPATH}/se3/includes/config.inc.php ]
then
	dbhost=`cat ${WWWPATH}/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat ${WWWPATH}/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat ${WWWPATH}/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat ${WWWPATH}/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`

	BASEDN=$(mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N -e "SELECT value FROM params WHERE name='ldap_base_dn'")
	if [ -z "$BASEDN" ]; then
	        echo "Impossible d'acceder au parametre BASEDN"
	        exit 1
	fi
	PARCSRDN=$(mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N -e "SELECT value FROM params WHERE name='parcsRdn'")
	if [ -z "$PARCSRDN" ]; then
	        echo "Impossible d'acceder au parametre PARCSRDN"
	        exit 1
	fi
	/usr/share/se3/sbin/isMemberOf.pl prof "cn=$2,$PARCSRDN,$BASEDN" >/dev/null 2>&1
	if [ "$?" != "0" ]
	then
		echo "$2 ne fait pas parti du parc $1."

		exit 1
	fi
else
	exit 1
fi
exit 0
