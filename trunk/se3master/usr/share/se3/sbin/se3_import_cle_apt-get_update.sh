#!/bin.bash

#
##### Script destine a recuperer les cles apres lesquelles apt-get update rale parfois #####
#
# Auteur: Stephane Boireau (ex-Animateur TICE vire comme un malpropre par un regime qui ne voit que par les faveurs au prive et le degraissage du public)
#
## $Id$ ##
#
# /usr/share/se3/scripts/se3_import_cle_apt-get_update.sh

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

if [ -z "$1" -o "$1" = "-h" -o "$1" = "--help" ]; then
	echo -e "${COLINFO}USAGE:${COLTXT} Passer en parametre la chaine de caracteres correspondant a la cle"
	echo -e "       reclamee par apt-get update"
	exit
fi

echo -e "${COLTXT}"
echo "Recuperation de la cle..."
echo -e "${COLCMD}"
gpg --keyserver pgpkeys.mit.edu --recv-key $1
if [ "$?" != "0" ]; then
	echo -e "${COLERREUR}"
	echo -e "ERREUR${COLTXT} lors de la recuperation."
	echo -e "${COLTXT}"
	echo "Abandon."
	exit
fi

echo -e "${COLTXT}"
echo "Prise en compte de la cle..."
echo -e "${COLCMD}"
gpg -a --export $1 | apt-key add -

echo -e "${COLTITRE}"
echo "Termine."
echo -e "${COLTXT}"
