#!/bin/bash

#
## $Id$ ##
#
##### Convertion de l'annuaire LDAP du format Samba 2 au format samba 3 #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Convertion de l'annuaire pour l'integration de clients OSX"
	echo "Usage : Pas d'option"
	exit
fi	

#
# Récupération des paramètres mysql
#
if [ -e /var/www/se3/includes/config.inc.php ]; then
        dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "Fichier de conf inaccessible" >> $SE3LOG
		echo "sauve.sh: Status FAILED" >> $SE3LOG
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
        echo "Impossible d'accéder au paramètre PEOPLEDN"
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
SE3NAME=`cat /etc/samba/smb.conf | grep "netbios name" |cut -d"=" -f2 | sed -e "s/ //g"`

# On recupere les anciennes entrees
echo "" > /tmp/apple_mod.ldif

ldapsearch -xLLL -D $ADMINRDN,$BASEDN -w $ADMINPW -b $PEOPLERDN,$BASEDN objectCLass=posixAccount dn | grep dn | while read dn; do
	SUID=`echo $dn|cut -d"=" -f2 | cut -d"," -f1`
	echo "$dn" >> /tmp/apple_mod.ldif
	echo "changetype: modify">> /tmp/apple_mod.ldif
	echo "add: objectClass">> /tmp/apple_mod.ldif
	echo "objectClass: apple-user">> /tmp/apple_mod.ldif
	echo "">> /tmp/apple_mod.ldif
	echo "$dn" >> /tmp/apple_mod.ldif
	echo "changetype: modify">> /tmp/apple_mod.ldif
	echo "add: apple-user-homeDirectory">> /tmp/apple_mod.ldif
	echo "apple-user-homeDirectory: /Users/Network/se3/$SUID">> /tmp/apple_mod.ldif
	echo "">> /tmp/apple_mod.ldif
	echo "$dn" >> /tmp/apple_mod.ldif
	echo "changetype: modify">> /tmp/apple_mod.ldif
	echo "add: apple-user-homeurl">> /tmp/apple_mod.ldif
	echo "apple-user-homeurl: <homedir><url>smb://$SE3NAME/osx</url><path>$SUID</path></homedir>">> /tmp/apple_mod.ldif
	echo "">> /tmp/apple_mod.ldif
done


# On modifie les nouvelles entrees
ldapmodify -x -c -D $ADMINRDN,$BASEDN -w $ADMINPW -f /tmp/apple_mod.ldif
