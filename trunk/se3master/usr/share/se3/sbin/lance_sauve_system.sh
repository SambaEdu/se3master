#!/bin/bash
#
##### Script de sauvegarde de divers paramètres SE3 #####
#
# Auteur : Stephane Boireau (Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# Dernière modif: 07/04/2007

if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script permettant de consulter la configuration MySQL"
	echo "dans se3db pour lancer la sauvegarde:"
	echo " - de l'annuaire LDAP"
	echo " - de /etc"
	echo " - des bases MySQL suivantes: 'se3db' et 'mysql'"
	echo " - de /var/lib/samba ou juste /var/lib/samba/secrets.tdb"
	echo ""
	echo "Usage : Lancer sans paramètres"
	exit
fi

# Fix Lenny
export LANG=fr_FR.UTF-8

#
# Récupération des paramètres mysql
#
if [ -e /var/www/se3/includes/config.inc.php ]; then
	dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible"
	# ENVOYER UN MAIL... A FAIRE
	exit 1
fi

#
# Récupération des paramètres de sauvegarde
#
svgsyst_cnsv_hebdo=`echo "SELECT value FROM params WHERE name='svgsyst_cnsv_hebdo'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
svgsyst_varlibsamba=`echo "SELECT value FROM params WHERE name='svgsyst_varlibsamba'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
svgsyst_aclvarse3=`echo "SELECT value FROM params WHERE name='svgsyst_aclvarse3'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
svgsyst_timeout=`echo "SELECT value FROM params WHERE name='svgsyst_timeout'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

if [ -z "$svgsyst_cnsv_hebdo" ]; then
	svgsyst_cnsv_hebdo="0"
fi

if [ -z "$svgsyst_varlibsamba" ]; then
	svgsyst_varlibsamba="0"
fi

if [ -z "$svgsyst_aclvarse3" ]; then
	svgsyst_aclvarse3="1"
fi

if [ -z "$svgsyst_timeout" ]; then
	svgsyst_timeout="4500"
else
	# Contrôler que c'est bien un nombre
	test=$(echo $svgsyst_timeout | sed -e "s/[0-9]//g")
	if [ ! -z "$test" ]; then
		svgsyst_timeout="4500"
	fi
fi

options=""
if [ "$svgsyst_cnsv_hebdo" = "1" ]; then
	options="$options conservation_hebdo"
fi
if [ "$svgsyst_varlibsamba" = "1" ]; then
	options="$options varlibsamba"
fi
if [ "$svgsyst_aclvarse3" = "0" ]; then
	options="$options noacl"
fi

#
# Lancement de la sauvegarde
#
#echo /usr/share/se3/sbin/sauve_system.sh $svgsyst_timeout $options forcer
/usr/share/se3/sbin/sauve_system.sh $svgsyst_timeout $options forcer > /dev/null 2>&1

