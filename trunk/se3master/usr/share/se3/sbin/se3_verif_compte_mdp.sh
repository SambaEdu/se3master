#!/bin/bash

# $Id$

# Script destine à tester/controler un couple uid/pass pour Linux et Samba

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

# Parametres LDAP
BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
#ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
#PASSDN=$(cat /etc/ldap.secret)

if [ -z "$1" -o "$1" = "-h" -o "$1" = "--help" ]; then
	echo "USAGE: Passer en parametre(s):"
	echo "        \$1 l'uid de l'utilisateur"
	echo "        \$2 le mot de passe (si aucun mot de passe n'est fourni"
	echo "        la date de naissance extraite du gecos est testee)"
	exit
fi

uid=$1
if [ -z "$2" ]; then
	pass=$(ldapsearch -xLLL uid=${uid} gecos | grep "^gecos: " | sed -e "s/^gecos: //" | cut -d "," -f2)

	if [ -z "${pass}" ]; then
		echo -e "${COLERREUR}ERREUR: Le gecos n'a pas ete trouve pour cet utilisateur ou la date de naissance n'y est pas."
		echo -e "${COLTXT}"
		exit
	fi
else
	pass=$2
fi

test1=$(ldapsearch -xLLL -D uid=${uid},ou=People,$BASEDN -w ${pass} uid=${uid} >/dev/null 2>&1)
if [ "$?" = "0" ]; then
	echo -e "${COLTXT}Le couple compte/mot de passe ${COLINFO}${uid}${COLTXT}/${COLINFO}${pass}${COLTXT} est correct pour Linux/Ldap (acces web)."
else
	echo -e "${COLERREUR}Le couple compte/mot de passe ${COLINFO}${uid}${COLERREUR}/${COLINFO}${pass}${COLERREUR} n'est pas correct pour Linux/Ldap (acces web)."
fi

test2=$(smbclient -L 127.0.0.1 -U ${uid}%${pass} >/dev/null 2>&1)
if [ "$?" = "0" ]; then
	echo -e "${COLTXT}Le couple compte/mot de passe ${COLINFO}${uid}${COLTXT}/${COLINFO}${pass}${COLTXT} est correct pour Samba/Window$."
else
	echo -e "${COLERREUR}Le couple compte/mot de passe ${COLINFO}${uid}${COLERREUR}/${COLINFO}${pass}${COLERREUR} n'est pas correct pour Samba/Window$."
fi
echo -e "${COLTXT}"
