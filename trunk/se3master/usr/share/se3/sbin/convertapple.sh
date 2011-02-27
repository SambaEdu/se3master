#!/bin/bash

#
## $Id: convertsambaschema.sh 660 2005-10-27 15:51:18Z plouf $ ##
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
# R�cup�ration des param�tres mysql
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
        echo "Impossible d'acc�der au param�tre BASEDN"
        exit 1
fi
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PEOPLERDN" ]; then
        echo "Impossible d'acc�der au param�tre PEOPLEDN"
        exit 1
fi
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINRDN" ]; then
        echo "Impossible d'acc�der au param�tre ADMINRDN"
        exit 1
fi
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINPW" ]; then
        echo "Impossible d'acc�der au param�tre ADMINPW"
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
