#!/bin/bash

#
## $Id$ ##
#
##### Permet de faire des recherches dans l'annuaire LDAP #####
#
# Derniere modif: 20130217

#. /usr/share/se3/sbin/variables_admin_ldap.sh lib > /dev/null
. /etc/se3/config_l.cache.sh

BASEDN=$ldap_base_dn
ROOTDN=cn=admin,$BASEDN
PASSDN=$adminPw

#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m" # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m" # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

if [ -z "$1" ]; then
	echo -e "$COLERREUR"
	echo "ERREUR: Le motif recherche doit etre de la forme cn=XXX ou uid=XXX"
	echo "        ou uid=XXX*"
	echo -e "$COLTXT"
	exit
fi

motif=$1

t=$(echo "$motif"|grep "=")
pref=$(echo $motif|cut -d"=" -f1)
valeur_motif=$(echo $motif|cut -d"=" -f2)
#echo "pref=$pref"
#echo "valeur_motif=$valeur_motif"
if [ -z "$t" -o -z "$pref" -o -z "$valeur_motif" ]; then
	echo -e "$COLERREUR"
	echo "ERREUR: Le motif recherche doit etre de la forme cn=XXX ou uid=XXX"
	echo "        ou uid=XXX*"
	echo -e "$COLTXT"
	#exit

	liste_pref[1]="cn"
	liste_pref[2]="uid"
	liste_pref[3]="employeeNumber"
	liste_pref[4]="gecos"
	liste_pref[5]="uidNumber"
	liste_pref[6]="gidNumber"
	liste_pref[7]="ipHostNumber"
	liste_pref[8]="macAddress"

	REP=""
	while [ -z "$REP" ]
	do
		echo -e "Veuillez choisir parmi les attributs suivants:
(${COLCHOIX}1${COLTXT}) cn        (${COLCHOIX}2${COLTXT}) uid       (${COLCHOIX}3${COLTXT}) employeeNumber     (${COLCHOIX}4${COLTXT}) gecos
(${COLCHOIX}5${COLTXT}) uidNumber (${COLCHOIX}6${COLTXT}) gidNumber (${COLCHOIX}7${COLTXT}) ipHostNumber       (${COLCHOIX}8${COLTXT}) macAddress"
		echo -e "Votre choix: [${COLDEFAUT}2${COLTXT}] $COLSAISIE\c"
		read REP

		if [ -z "$REP" ]; then
			REP=2
		fi

		if [ -z "${liste_pref[$REP]}" ]; then
			echo -e "$COLERREUR"
			echo "Choix invalide"
			REP=""
		fi
	done
    pref=${liste_pref[$REP]}
    motif="$pref=$motif"
fi

liste_complete=""
liste_branche=""
liste_result=""
cpt=0
for branche in People Groups Computers Parcs Rights
do
	#echo "ldapsearch -xLLL -b ou=$branche,$BASEDN $motif $pref|grep \"^$pref: \"|sed -e \"s|^$pref: ||\""
	OLD_IFS=$IFS
	IFS="
"
	liste=($(ldapsearch -xLLL -b ou=$branche,$BASEDN $motif $pref|grep "^$pref: "|sed -e "s|^$pref: ||"))
	IFS=$OLD_IFS
	if [ -n "${liste[0]}" ]; then
		echo -e "$COLINFO"
		echo "Branche $branche:"
		n=0
		while [ "$n" -lt "${#liste[*]}" ]
		do
			echo -e "${COLCHOIX}$cpt${COLTXT} - ${liste[$n]}"
			liste_complete[$cpt]="-b ou=$branche,$BASEDN $pref=\"${liste[$n]}\""
			liste_branche[$cpt]="-b ou=$branche,$BASEDN"
			liste_result[$cpt]=${liste[$n]}
			n=$((n+1))
			cpt=$((cpt+1))
		done
	fi
done

if [ "$cpt" = "0" ]; then
	echo -e "$COLTXT"
	echo "Aucune entree n'a ete trouvee pour le motif $motif"
	exit
fi

REP=""
while [ -z "$REP" ]
do
	echo -e "$COLTXT"
	echo -e "Numero de l'entree a afficher: [${COLDEFAUT}0${COLTXT}] $COLSAISIE\c"
	read REP

	if [ -z "$REP" ]; then
		REP=0
	fi

	if [ -n "$REP" -a -z "${liste_complete[$REP]}" ]; then
		echo -e "$COLERREUR"
		echo "Choix invalide"
		REP=""
	fi
done

debug="n"
if [ "$debug" = "y" ]; then
	echo "Liste des entrees:"
	n=0
	while [ "$n" -lt "${#liste_complete[*]}" ]
	do
		echo "liste_complete[$n]=${liste_complete[$n]}"
		n=$((n+1))
	done
fi

echo -e "$COLCMD"
#echo ldapsearch -xLLL -D $ROOTDN -w$PASSDN ${liste_complete[$REP]}
#ldapsearch -xLLL -D $ROOTDN -w$PASSDN ${liste_complete[$REP]}
ldapsearch -xLLL -D $ROOTDN -w$PASSDN ${liste_branche[$REP]} $pref="${liste_result[$REP]}"

echo -e "$COLTXT"

