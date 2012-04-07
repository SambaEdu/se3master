#!/bin/bash
#
##### Script permettant d'autoriser ou non l'acces au partage Docs/public #####
#
# Auteur : Stephane Boireau (Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# Derniere modif: 07/04/2012

dossier=/var/se3/Docs/public

if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script permettant de bloquer/restaurer l'acces au partage $dossier"
	echo ""
	echo "Usage : Passer en parametre:"
	echo "          autoriser=y"
	echo "        ou"
	echo "          autoriser=n"
	echo "        Et eventuellement:"
	echo "          recursif"
	echo "        pour traiter les sous-dossiers."
	exit
fi

OPT=""
t0=$(echo "$*"|grep "recursif")
if [ -n "$t0" ]; then
	OPT="-R"
fi

RESET() {
	setfacl -b $dossier
}

INITIALISATION() {
	chown admin:admins $dossier

	setfacl $OPT -m u:admin:rwx $dossier
	setfacl $OPT -m d:u:admin:rwx $dossier
	setfacl $OPT -m g:admins:rwx $dossier
	setfacl $OPT -m d:g:admins:rwx $dossier
}

ECRITURE_POUR_TOUS() {
	chmod 777 $dossier
	setfacl $OPT -m d:m:rwx $dossier
	setfacl $OPT -m o::rwx $dossier
	setfacl $OPT -m o::rwx $dossier
}

INTERDICTION_ACCES() {
	chmod 770 $dossier
}

t1=$(echo "$*"|grep "autoriser=y")
if [ -n "$t1" ]; then
	echo "Autorisation de l'acces/ecriture sur: $dossier"
	INITIALISATION
	ECRITURE_POUR_TOUS
else
	t2=$(echo "$*"|grep "autoriser=n")
	if [ -n "$t2" ]; then
		echo "Interdiction de l'acces/ecriture sur: $dossier"
		RESET
		INITIALISATION
		INTERDICTION_ACCES
	else
		echo "Choix d'action inconnu???"
	fi
fi

