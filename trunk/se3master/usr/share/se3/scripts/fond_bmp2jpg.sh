#!/bin/bash
#
##### Script de conversion BMP2PNG pour affichage dans interface web #####
#
# Auteur: St�phane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# /usr/share/se3/sbin/fond_bmp2jpg.sh
# Derni�re modification: 23/05/2006

# Dossier de stockage des fonds communs:
dossier_base_fond="/var/se3/Docs/media/fonds_ecran"

if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script permettant de convertir un fond d'�cran BMP en PNG pour permettre"
	echo "un aper�u dans l'interface web..."
	echo "Le fond d'�cran BMP doit se trouver dans $dossier_base_fond"
	echo "Il doit se nommer \$groupe.bmp"
	echo ""
	echo "Usage : Passer en param�tre le nom du groupe."
	exit
fi	


#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m"     # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

# Dossier dans l'interface web:
dossier_web_tmp="/var/www/se3/Admin/fonds_ecran"
mkdir -p $dossier_web_tmp
chown www-se3 $dossier_web_tmp

if [ -z "$1" -o ! -e "/usr/bin/convert" ]; then
	echo -e "$COLERREUR"
	echo "ERREUR:"
	echo -e "$COLTXT\c"
	echo "        Ce script sert � convertir des images BMP utilis�es pour des fonds"
	echo "        d'�cran en images PNG pour permettre leur affichage dans l'interface"
	echo "        web."
	echo "        Pour fonctionner, ce script n�cessite l'installation d'ImageMagick."
	echo "        Usage: Passer en param�tre le nom du groupe."
	exit
else

	# Cr�ation du groupe overfill s'il n'existe pas.
	if [ "$1" = "overfill" ]; then
		if [ -z "$(ldapsearch -xLLL cn=overfill)" ]; then
			/usr/share/se3/sbin/groupAdd.pl 1 overfill "Personnes dont le home d�passe un quota..."
		fi
	fi

	# Tests de la validit� de la demande de conversion:
	test1=$(ldapsearch -xLLL uid=$1)
	test2=$(ldapsearch -xLLL cn=$1)
	if [ -z "$test1" -a -z "$test2" ]; then
		echo -e "$COLERREUR"
		echo "ERREUR:"
		echo -e "$COLTXT\c"
		echo "        L'utilisateur/groupe propos� n'existe pas dans l'annuaire."
		exit
	fi

	if [ ! -e "$dossier_base_fond/$1.bmp" ]; then
		echo -e "$COLERREUR"
		echo "ERREUR:"
		echo -e "$COLTXT\c"
		echo "        L'image propos�e n'existe pas dans $dossier_base_fond"
		exit
	fi

	#if ! file "$dossier_base_fond/$1.bmp" | grep "PC bitmap data" > /dev/null; then
	if ! file "$dossier_base_fond/$1.bmp" | grep "PC bitmap" > /dev/null; then
		echo -e "$COLERREUR"
		echo "ERREUR:"
		echo -e "$COLTXT\c"
		echo "        L'image propos�e n'est pas une image BMP."
		exit
	fi

	# Conversion proprement dite:
	/usr/bin/convert "$dossier_base_fond/$1.bmp" "$dossier_web_tmp/$1.jpg"
	chown www-se3 "$dossier_web_tmp/$1.jpg"
fi
