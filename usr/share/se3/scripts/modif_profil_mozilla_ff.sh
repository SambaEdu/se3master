#!/bin/bash


## $Id$ ##


#script permettant de modfier la page de demarrage de mozilla FF
# Franck.molle@ac-rouen.fr version 0.1 - juin 2005



ERREUR()
{
	echo ""
	echo "ERREUR!"
	echo -e "$1"
	echo ""
	exit 1
}


WWWPATH="/var/www"
chemin_html="/var/www/se3/tmp"

function RAPPORTHTML() {
echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<title>Traitement des profils</title>
</head>
<body>
<h1 align=\"center\">Traitement des profils</h1>
<p align=\"center\">Traitement $1</p>
</body>
</html>" > $chemin_html/recopie_profils_firefox.html

}

if [ ! $# -ge 1 ]; then
	echo ""
	echo  "$0 a besoin d'arguments pour fonctionner."
	echo "Passer en arguments dans l'ordre :"
	echo "- le nom du groupe ou de l'utilisateur dont vous voulez modifier la page de démarrage de Firefox,"
	echo "- l'url de la nouvelle page de démarrage,"
	echo "- eventuellement ajouter create_homes en argument si vous voulez créer les homes non encore existants avant de modifier la page de démarrage."
	echo "ex1 : ./modif_profil_mozilla profs http://www.google.fr create_homes"
	echo "fixera la page de démarrage de mozilla firefox à google.fr pour tous les profs et créera les homes si besoin avant de le faire."
	echo ""
	echo "ex2 : ./modif_profil_mozilla eleves http://www.google.fr"
	echo "fixera la page de démarrage de mozilla firefox à google.fr pour tous les élèves et ignorera les homes non encore existants."
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


if [ "$1" == "skeluser" ]; then
	echo "Je change la page de demarrage pour le squelette en lui fixant $2"
	sed -e "/(\"browser.startup.homepage\",/d" -i ${CHEMIN_FF_SOURCE}/Firefox/Profiles/default/prefs.js
	echo "user_pref(\"browser.startup.homepage\", \"$2\");'" >> ${CHEMIN_FF_SOURCE}/Firefox/Profiles/default/prefs.js
	exit 0
fi


	### recuperation des parametres actuels de l'annuaire dans la base ####
	BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	
	TST_GRP=$(ldapsearch -xLLL cn=$1 -b $BASEDN | grep member)
	
	if [ -z "$TST_GRP" ]; then
	TST_UID=$(ldapsearch -xLLL uid="$1" uid)
		if [ -z "$TST_UID" ]; then
			ERREUR "Impossible de trouver le groupe ou l'utilisateur pass� en param�tre dans l'annuaire Ldap."
		else
			if [ -z "$NEW_PAGE_DEM" ]; then
				if [ -e /home/$1 ]; then
					rm -rf /home/$1/profil/appdata/Mozilla/Firefox
					cp -r $CHEMIN_FF_SOURCE/Firefox /home/$1/profil/appdata/Mozilla/
					chown -R $1:lcs-users /home/$1/profil/appdata/Mozilla/Firefox
				else
					/usr/share/se3/shares/shares.avail/mkhome.sh "$1"
				fi
				
				exit 0
			fi
			
			if [ "$OPTION" == "create_homes" ]; then
				/usr/share/se3/shares/shares.avail/mkhome.sh "$1"
			fi
			echo "Je change la page de démarrage pour $1 en lui fixant $2"
			if [ -e /home/$1 ]; then
			    sed -e "/(\"browser.startup.homepage\",/d" -i /home/$1/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			    echo "user_pref(\"browser.startup.homepage\", \"$2\");'" >> /home/$1/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			else
			    echo "Le home de l'utilisateur $1 n'existe pas et a �t� ignor�, relancer la script avec l'option create_homes en 3eme argument si vous voulez le cr�er."
			fi
			exit 0	
		fi
	fi
	TST_GRP_POSIX=$(ldapsearch -xLLL "cn=$1" | grep memberUid)
	if [ -z "$TST_GRP_POSIX" ]; then
		ldapsearch -x -LLL cn=$1 -b $BASEDN | grep uid | cut -d " " -f2 |  cut -d "=" -f2 | cut -d "," -f1 | while read A
		do
			if [ "$OPTION" == "create_homes" ]; then
			/usr/share/se3/shares/shares.avail/mkhome.sh "$A"
			fi
			
			if [ -e /home/$A ]; then
			echo "Je change la page de demarrage pour $A en lui fixant $2"
			sed -e "/(\"browser.startup.homepage\",/d" -i /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			echo "user_pref(\"browser.startup.homepage\", \"$2\");'" >> /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
			else
			echo "Le home de l'utilisateur $A n'existe pas et a été ignoré, relancer le script avec l'option create_homes en 3eme argument si vous voulez le créer."
			fi
		done
	
	else
		RAPPORTHTML "du groupe $1 en cours"
		ldapsearch -x -LLL "cn=$1" | grep memberUid | cut -d " " -f2 | while read A
		do 
			if [ -z "$NEW_PAGE_DEM" ]; then
				if [ -e /home/$A ]; then
					echo "Remplacement profil Firefox pour $A"
					rm -rf /home/$A/profil/appdata/Mozilla/Firefox
					cp -r $CHEMIN_FF_SOURCE/Firefox /home/$A/profil/appdata/Mozilla/
					chown -R $A:lcs-users /home/$A/profil/appdata/Mozilla/Firefox
				else
					echo "Creation espace perso de $A"
					/usr/share/se3/shares/shares.avail/mkhome.sh "$A"
				fi
				
			else
				if [ -e /home/$A ]; then
					echo "Je change la page pour $A en lui fixant $2"
					sed -e "/(\"browser.startup.homepage\",/d" -i /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
					echo "user_pref(\"browser.startup.homepage\", \"$2\");'" >> /home/$A/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
				else
					if [ "$OPTION" == "create_homes" ]; then
						/usr/share/se3/shares/shares.avail/mkhome.sh "$A"
					else
						echo "Le home de l'utilisateur $A n'existe pas et a été ignoré, relancer la script avec l'option create_homes en 3eme argument si vous voulez le créer."
					fi
				
				fi

				
			fi
			
			
		done
		RAPPORTHTML "du groupe $1 Ok"
	fi

exit 0
