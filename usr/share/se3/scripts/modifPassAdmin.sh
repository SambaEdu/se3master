#!/bin/bash

#
## $Id$ ##
#
##### Change le mot de passe du cn=admin pour binder l'annuaire #####

if [ "$1" = "--help" -o "$1" = "-h" -o "$1" = "" ]
then
	echo "Change le mot de passe de l'admin LDAP"
	echo "Usage : modifPassLdap.sh nouveau_mot_de_passe"
	exit
fi	

WWWPATH="/var/www/"

#
## recuperation des variables necessaires pour interoger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
        dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "Fichier de conf inaccessible désolé !!"
        echo "le script ne peut se poursuivre"
        exit 1
fi
/usr/bin/mysql -u $dbuser -p$dbpass -D $dbname -e "UPDATE params SET value='$1' WHERE name='adminPw'"

# On controle

pass_new=`/usr/bin/mysql -u $dbuser -p$dbpass -D $dbname -e "SELECT * from params WHERE name='adminPw' AND value='$1'"`
if [ "$pass_new" = "" ]
then
	echo "Erreur : Le mot de passe dans la base SQL n'est pas correct"
	exit 1
fi	

/usr/share/se3/scripts/mkSlapdConf.sh

exit 0
