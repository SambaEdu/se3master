#!/bin/bash

# $Id$
# Script destiné à virer tous les membres d'un groupe
# Auteur: Stéphane Boireau
# Dernière modification: 26/02/2007

if [ "$1" == "--help" -o "$1" == "-h" ]
then
        echo "Script de suppression des membres d'un groupe"
        echo "Passer (éventuellement) en paramètre le nom du groupe."
        echo "--help cette aide"

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

echo -e "$COLTITRE"
echo "***************************"
echo "*  SCRIPT DE SUPPRESSION  *"
echo "* DES MEMBRES D'UN GROUPE *"
echo "***************************"

if [ ! -z "$1" ]; then
	groupe=$1
else
	echo -e "$COLTXT"
	echo "Veuillez saisir le nom du groupe dont vous souhaitez supprimer tous les membres."
	echo "Le groupe lui-même ne sera pas supprimé."
	echo -e "Groupe: $COLSAISIE\c"
	read groupe
fi

echo -e "$COLTXT"
echo -e "Vous avez choisi le groupe ${COLINFO}$groupe"

BASEDN=$(cat /etc/ldap/ldap.conf | grep '^BASE' | tr "\n" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)

test=$(ldapsearch -xLLL -b ou=Groups,$BASEDN cn=$groupe)
if [ -z "$test" ]; then
	echo -e "$COLERREUR"
	echo "ERREUR: Le groupe choisi n'existe pas."
	echo -e "$COLTXT"
	exit
fi

ROOTDN=$(cat /etc/ldap/slapd.conf | grep '^rootdn' | tr '\n' ' ' | cut -d'"' -f2)
PASSDN=$(cat /etc/ldap.secret)

ladate=$(date +"%Y.%m.%d-%H.%M.%S");
tmp=/root/tmp/nettoyage_groupe_${groupe}_${ladate}
mkdir -p $tmp

# Sauvegarde:
echo -e "$COLCMD"
#ldapsearch -xLLL -b ou=Groups,$BASEDN cn=$groupe > $tmp/svg_$groupe.ldif
ldapsearch -xLLL -D $ROOTDN -w $PASSDN -b ou=Groups,$BASEDN cn=$groupe > $tmp/svg_$groupe.ldif

type_grp=$(ldapsearch -xLLL -b ou=Groups,$BASEDN cn=$groupe objectClass | grep "^objectClass: " | sed -e "s/^objectClass: //" | egrep "(groupOfNames|posixGroup)")
if [ "$type_grp" = "posixGroup" ]; then
	echo "dn: cn=$groupe,ou=Groups,$BASEDN
changetype: modify
delete: memberUid" > $tmp/vider_$groupe.ldif
else
	echo "dn: cn=$groupe,ou=Groups,$BASEDN
changetype: modify
delete: member" > $tmp/vider_$groupe.ldif
fi

ldapmodify -x -D $ROOTDN -w $PASSDN -f $tmp/vider_$groupe.ldif

# Nettoyage
#rm -fr $tmp

echo -e "$COLTITRE"
echo "Terminé"
echo -e "$COLTXT"
