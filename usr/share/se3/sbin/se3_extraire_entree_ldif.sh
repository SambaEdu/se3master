#!/bin/bash

# $Id$

# Auteur: Stephane Boireau

# Script destine a recuperer une entree d'un fichier LDIF
# Demarche qui peut etre utile pour recuperer un compte d'utilisateur un peu vite expedie a la corbeille.
# Il faut disposer d'une sauvegarde dans laquelle recuperer l'entree.

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

if [ -z "$2" ]; then
	echo -e "${COLERREUR}USAGE:${COLTXT} Passer en parametres:"
	echo -e "        ${COLINFO}\$1${COLTXT} le critere de recherche:"
	echo -e "           ${COLCHOIX}cn=Profs${COLTXT} ou ${COLCHOIX}uid=toto${COLTXT} par exemple"
	echo -e "        ${COLINFO}\$2${COLTXT} le fichier LDIF"
	echo -e ""
	echo -e "Attention: Seule la premiere entree (*) du fichier est extraite."
	echo -e "           Si votre fichier LDIF est normal, cela ne doit pas arriver."
	echo -e ""
	echo -e "           (*) correspondant au critere."
	exit
fi

if [ ! -e "$2" ]; then
	echo -e "${COLERREUR}ERREUR: Le fichier $2 n'existe pas.${COLTXT}"
	exit
fi

motif=$1
fichier_ldif=$2
extraction=$(echo "${motif}" | sed -e "s/[^A-Za-z0-9]/_/g")

echo -e "${COLCMD}"
grep -A$(wc -l ${fichier_ldif} | cut -d" " -f1) "dn: ${motif}" ${fichier_ldif} | while read A
do
	if [ -z "$A" ]; then
		echo -e "${COLTITRE}"
		echo "Extraction terminee."
		echo -e "${COLTXT}"
		exit
	else
		echo ${A} | tee -a ${extraction}.ldif
	fi
done
