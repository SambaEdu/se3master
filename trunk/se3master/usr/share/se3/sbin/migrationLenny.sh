#!/bin/bash

#### ATTENTION
#	Ne gere pas encore les comptes de Trash
###

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
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PEOPLERDN" ]; then
        echo "Impossible d'accéder au paramètre PEOPLERDN"
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

# On cherche l'ip machine courante
ldapsearch -xLLL -D $ADMINRDN,$BASEDN -w $ADMINPW objectClass=person uid| grep uid:| cut -d ' ' -f2| while read uid
do
		(
		echo "dn: uid=$uid,$PEOPLERDN,$BASEDN"
		echo "changetype: modify"
		echo "replace: sambaPwdLastSet"
		echo "sambaPwdLastSet: 1"
		) | ldapmodify -x -D $ADMINRDN,$BASEDN -w $ADMINPW >/dev/null 2>&1
		if [ "$?" != "0" ]
		then
			#corbeille
			  (
	                echo "dn: uid=$uid,ou=Trash,$BASEDN"
	                echo "changetype: modify"
	                echo "replace: sambaPwdLastSet"
	                echo "sambaPwdLastSet: 1"
	                ) | ldapmodify -x -D $ADMINRDN,$BASEDN -w $ADMINPW >/dev/null

		fi
done
