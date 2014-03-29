#!/bin/bash

## $Id$ ##
#
##### script permettant l'appel à diverses fonctions #####
#

function getmypasswd {

WWWPATH="/var/www"
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible."
	exit 1
fi
}

# Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m"     # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan


ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$1"
	echo -e "$COLTXT"
	exit 1
}
POURSUIVRE()
{
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Peut-on poursuivre? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done

	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		ERREUR "Abandon!"
	fi
}

REPORT_FILE="$(tempfile)"
MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail a  l'admin
	echo "$REPORT"  | mail -s "$1" $MAIL_ADMIN
fi
}

LINE_TEST()
{
if ( ! wget -q --output-document=/dev/null 'http://wawadeb.crdp.ac-caen.fr/index.html') ; then
	ERREUR "Votre connexion internet ne semble pas fonctionnelle !!" 
	exit 1
else
	echo "connexion internet Ok"
fi
}


while getopts ":h" cmd
do
	case $cmd in	
	h) echo "script permettant d'initialiser un ensemble de fonctions utiles" 
	echo "usage: $0 aucune option | -h pour mode verbeux
liste des fonctions disponibles :
ERREUR  : prend en argument le message à afficher
POURSUIVRE : sans argument, permet de demander de poursuivre le script ou de l'abandonner
MAIL_REPORT : envoi un mail a l'admin si ssmtp configuré, prend en argument le sujet. Le contenu du mail sera celui de la variable \$REPORT_FILE que l'on aura au préalable complété avec \">\". 
Ex : echo \"pas assez de place sur la partition\" > \$REPORT_FILE.  MAIL_REPORT \"se3 : avertissement\".
SETMYSQL : permet d'inserer une entree ds la table params de se3db. 
SETMYSQL \"name\" \"valeur\" \"description\" \"n° categorie\"
"
	exit 0;;
	esac
done


function SETMYSQL {
# set se3db param
# SETMYSQL nom valeur description categorie
# categories : config : 1
#              ldap   : 2
#              pathse3: 3
#              backup : 4
#              cache  : 5
#              systeme: 6
#              dhcp   : 7

getmypasswd
test_exist=`echo "SELECT id FROM params WHERE name='$1'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ "X$test_exist" = "X" ]; then # if empty
    echo "insert into params set name='$1',value='$2',descr='$3',cat='$4';" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
else
    echo "UPDATE params SET value='$2' WHERE name='$1';" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
fi
}

function CHANGEMYSQL {
# change se3db param
# ChangeMYSQL nom valeur 

getmypasswd
echo "UPDATE params SET value='$2' WHERE name='$1';" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
}



