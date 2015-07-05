#!/bin/bash


## $Id$ ##


[[ "$#" != "2" ]] && exit 1

if [ -e /var/www/se3/includes/config.inc.php ]; then
        dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "Fichier de conf inaccessible" >> $SE3LOG
        exit 1
fi

#
# Recuperation des params LDAP
#

BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
        echo "Impossible d'accéder au paramètre BASEDN"
        exit 1
fi
PARCSRDN=`echo "SELECT value FROM params WHERE name='parcsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PARCSRDN" ]; then
        echo "Impossible d'accéder au paramètre PARCSRDN"
        exit 1
fi
PRINTERSRDN=`echo "SELECT value FROM params WHERE name='printersRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PRINTERSRDN" ]; then
        echo "Impossible d'accéder au paramètre PRINTERSRDN"
        exit 1
fi
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINRDN" ]; then
        echo "Impossible d'accéder au paramètre ADMINRDN"
        exit 1
fi
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINPW" ]; then
        echo "Impossible d'accéder au paramètre ADMINPW"
        exit 1
fi

(
	echo "dn: cn=$2,$PARCSRDN,$BASEDN"
	echo "changetype: modify"
	echo "replace: owner"
	echo "owner: cn=$1,$PRINTERSRDN,$BASEDN"
) | ldapmodify -x -D $ADMINRDN,$BASEDN -w $ADMINPW >/dev/null
