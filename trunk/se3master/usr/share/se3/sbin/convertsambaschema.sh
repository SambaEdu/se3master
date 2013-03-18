#!/bin/bash

#
## $Id$ ##
#
##### Convertion de l'annuaire LDAP du format Samba 2 au format samba 3 #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Convertion de l'annuaire du format Samba2 au format Samba 3"
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
DOMAINSID=`echo "SELECT value FROM params WHERE name='domainsid'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$DOMAINSID" ]; then
        echo "Impossible d'accéder au paramètre DOMAINSID"
        exit 1
fi
SMBVERSION=`echo "SELECT value FROM params WHERE name='smbversion'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$SMBVERSION" ]; then
        echo "Impossible d'accéder au paramètre smbversion"
        exit 1
fi
if [ ! "$SMBVERSION" = "samba3" ]; then
	echo "Version de samba incorrecte."
	exit 1
fi

#
# Conversion de schema
#

# On recupere les anciennes entrees

ldapsearch -xLLL -D $ADMINRDN,$BASEDN -w $ADMINPW objectCLass=sambaAccount > /tmp/cnvrt_old.ldif

# On convertit le ldif
/usr/share/se3/sbin/convertSambaAccount --input /tmp/cnvrt_old.ldif --output /tmp/cnvrt_mod.ldif --sid $DOMAINSID --changetype modify

# On supprime les anciennes entrees
#cat /tmp/cnvrt_old.ldif |grep dn: |cut -c 5- >/tmp/cnvrt_del.ldif
#ldapdelete -x -D $ADMINRDN,$BASEDN -w $ADMINPW -f /tmp/cnvrt_del.ldif

# On modifie les nouvelles entrees
ldapmodify -x -c -D $ADMINRDN,$BASEDN -w $ADMINPW -f /tmp/cnvrt_mod.ldif
