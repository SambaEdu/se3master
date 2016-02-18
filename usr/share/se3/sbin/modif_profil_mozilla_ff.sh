#!/bin/bash

#
## $Id$ ##
#
##### Script permettant de modfier la page de demarrage de mozilla FF #####
# Franck.molle@ac-rouen.fr - juin 2005

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script permettant de modifier la page de demarrage de Mozilla FF"
	echo "Usage : aucune option"
	exit
fi

#Couleurs
COLTITRE="\033[1;35m"	# Rose
COLPARTIE="\033[1;34m"	# Bleu
COLTXT="\033[0;37m"	# Gris
COLCHOIX="\033[1;33m"	# Jaune
COLDEFAUT="\033[0;33m"	# Brun-jaune
COLSAISIE="\033[1;32m"	# Vert
COLCMD="\033[1;37m"	# Blanc
COLERREUR="\033[1;31m"	# Rouge
COLINFO="\033[0;36m"	# Cyan

ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$1"
	echo -e "$COLTXT"
	exit 1
}
WWWPATH="/var/www"

if [ $# -ne 3 -a $# -ne 2 ]; then
	echo -e "$COLERREUR\c"
	echo -e "$0 a besoin d'arguments pour fonctionner"
	echo -e "$COLINFO\c"
	echo "Passer en arguments dans l'ordre :"
	echo "- le nom du groupe ou de l'utilisateur dont vous voulez modifier la page de demarrafe de FireFox"
	echo "- l'url de la nouvelle page de demarrage"
	echo "- eventuellement ajouter create_homes en argument si vous voulez creer les homes non encore existant avant de modifier la page de demarrage"
	echo -e "$COLTXT"
	echo "ex1 : ./modif_profil_mozilla profs http://www.google.fr create_homes"
	echo "fixera la page de demarrage de mozilla firefox a google.fr pour tous les profs et creera les homes si besoin avant de le faire"
	echo ""
	echo "ex2 : ./modif_profil_mozilla eleves http://www.google.fr"
	echo "fixera la page de demarrage de mozilla firefox a google.fr pour tous les élèves et ignorera les homes non encore existant"
	exit 1
fi

NEW_PAGE_DEM="$2"
OPTION="$3"

## recuperation des variables necessaires pour interoger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f2 | cut -d \" -f2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut	-d = -f 2 |cut -d \" -f 2`
 	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
 	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
	ERREUR "Fichier de configuration inaccessible, le script ne peut se poursuivre."
	
fi
path2UserSkel=`echo "SELECT value FROM params WHERE name='path2UserSkel'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$path2UserSkel" ]; then
	path2UserSkel="/etc/skel/user"
fi

CHEMIN_FF_SOURCE="${path2UserSkel}/profil/appdata/Mozilla"


if [ "$1" == ""skeluser ]; then
echo "je change la page de demarrage pour le squelette en lui fixant $2"
			cat  ${CHEMIN_FF_SOURCE}/Firefox/Profiles/default/prefs.js | sed -e "/(\"browser.startup.homepage\",/d" > ${CHEMIN_FF_SOURCE}/Firefox/Profiles/default/prefs.js 
			echo "user_pref(\"browser.startup.homepage\", \"$2\");'" >> ${CHEMIN_FF_SOURCE}/Firefox/Profiles/default/prefs.js 
			exit 0
fi


	### recuperation des parametres actuels de l'annuaire dans la base ####
	BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	
	TST_GRP=$(ldapsearch -xLLL cn=$1 -b $BASEDN | grep member)
	
	if [ -z "$TST_GRP" ]; then
	TST_UID=$(ldapsearch -xLLL uid="$1")
		if [ -z "$TST_UID" ]; then
			ERREUR "Impossible de trouver le groupe ou l'utilisateur passé en paramètre dans l'annuaire Ldap"
		else
			if [ "$OPTION" == "create_homes" ]; then
			/usr/share/se3/sbin/mkhome.pl "$1"
			fi
			echo "je change la page de demarrage pour $1 en lui fixant $2"
			if [ -e /home/$1 ]; then
			cat  /home/$1/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js | sed -e "/(\"browser.startup.homepage\",/d" > /home/$1/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			echo "user_pref(\"browser.startup.homepage\", \"$2\");'" >> /home/$1/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			else
			echo "le home de l'utilisateur $1 n'existe pas et a été ignoré, relancer la script avec l'option create_homes en 3eme argument si vous voulez le créer"
			fi
			exit 0	
		fi
	fi
	TST_GRP_POSIX=$(ldapsearch -xLLL "cn=$1" | grep memberUid)
	if [ -z "$TST_GRP_POSIX" ]; then
		ldapsearch -x -LLL cn=$1 -b $BASEDN | grep uid | cut -d " " -f2 |  cut -d "=" -f2 | cut -d "," -f1 | while read A
		do
			if [ "$OPTION" == "create_homes" ]; then
			/usr/share/se3/sbin/mkhome.pl "$A"
			fi
			
			if [ -e /home/$A ]; then
			echo "je change la page de demarrage pour $A en lui fixant $2"
			cat  /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js | sed -e "/(\"browser.startup.homepage\",/d" > /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			echo "user_pref(\"browser.startup.homepage\", \"$2\");'" >> /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			else
			echo "le home de l'utilisateur $A n'existe pas et a été ignoré, relancer le script avec l'option create_homes en 3eme argument si vous voulez le créer"
			fi
		done
	
	else
		
		ldapsearch -x -LLL "cn=$1" | grep memberUid | cut -d " " -f2 | while read A
		do 
			if [ "$OPTION" == "create_homes" ]; then
			/usr/share/se3/sbin/mkhome.pl "$A"
			fi
			
			if [ -e /home/$A ]; then
			echo "je change la page de demarrage pour $A en lui fixant $2"
			cat  /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js | sed -e "/(\"browser.startup.homepage\",/d" > /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			echo "user_pref(\"browser.startup.homepage\", \"$2\");'" >> /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			else
			echo "le home de l'utilisateur $A n'existe pas et a été ignoré, relancer la script avec l'option create_homes en 3eme argument si vous voulez le créer"
			fi
			
		done
	fi

exit 0
