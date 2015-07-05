#!/bin/bash

#
## $Id$ ##
#
##### Test du changement de mot de passe pour les professeurs #####
# Stephane Boireau, Academie de Rouen
#
# Modification pour test sur n'importe quel groupe : Olivier Lacroix, Académie de Créteil

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
## recuperation des variables necessaires pour interoger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 | cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut	-d = -f 2 | cut -d \" -f 2`
 	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
 	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
	ERREUR "Fichier de configuration inaccessible, le script ne peut se poursuivre."
fi

if [ $# -ne 1 -o "$1" = "--help" -o "$1" = "-h" ]; then
        echo "Teste le changement des mots de passe pour les utilisateurs d'un groupe"
	echo -e "$COLERREUR\c"
	echo -e "$0 a besoin d'un argument pour fonctionner: le nom d'un groupe de l'annuaire"
	echo -e "$COLTXT"
	echo "ex1 : ./testmdp.sh Profs"
	echo "testera les mots de passe du groupe Profs"
	echo ""
	exit 1
fi

### recuperation des parametres actuels de l'annuaire dans la base ####
BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

TST_GRP=$(ldapsearch -xLLL cn="$1" -b $BASEDN | grep member)

if [ -z "$TST_GRP" ]; then
  TST_GRP=$(ldapsearch -xLLL cn="$1" -b $BASEDN)
  if [ -z "$TST_GRP" ]; then
    ERREUR "Impossible de trouver le groupe ou l'utilisateur passe en parametre dans l'annuaire Ldap"
  else
    echo "Le groupe est vide."
    exit 1
  fi
fi

HEURE=$(date|cut -d" " -f5)

ldapsearch -xLLL cn=$1 | grep memberUid | while read A
do
	uid=$(echo "$A" | cut -d" " -f2)
	date=$(ldapsearch -xLLL uid=$uid | grep "^gecos:" | cut -d"," -f2)
        #echo "user : $uid date : $date"
	if smbclient -L 127.0.0.1 -U $uid%$date > /dev/null; then
		#echo "Le professeur <A HREF="/annu/people.php?uid=$uid">$uid</A> a conserve $date pour mot de passe."
		echo "L'utilisateur <A HREF="/annu/people.php?uid=$uid">$uid</A> a conserve sa date de naissance pour mot de passe."
                echo "TOUTLEMONDEILNESTPASGENTIL" > /tmp/testmdp.$HEURE
	#else
		#~ echo "$uid est un bon petit soldat;o), il a bien changé son mot de passe."
	fi
done

if [ -e /tmp/testmdp.$HEURE ]; then
  rm /tmp/testmdp.$HEURE
else
  echo "Tous les membres ont change leur mot de passe initial. Que de bons petits soldats ;-)..."
fi
