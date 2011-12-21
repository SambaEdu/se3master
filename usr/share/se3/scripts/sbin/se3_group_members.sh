#!/bin/bash
#
# SambaEdu
#
# $Id$
#

WWWPATH="/var/www"

# recup parametres ldap
. /etc/se3/config_l.cache.sh
# recup parametres caches : 
. /etc/se3/config_m.cache.sh
. /etc/se3/config_d.cache.sh

if [ "$1" = "-h" -o "$1" = "--help" ]; then
	echo "Script destine a retourner la liste des membres d'un groupe (uid)."
	echo ""
	echo "USAGE: Passer en parametre le nom du groupe."
	echo "       Sinon, sans parametre, la liste des groupes est proposee."
	echo ""
	echo "       A IMPLEMENTER:"
	echo "       Si un groupe est passe en parametre, on peut donner ensuite,"
	echo "       la liste des attributs a recuperer."
	exit
fi

interactif="y"

groupe=""
if [ -n "$1" ]; then
	t=$(ldapsearch -xLLL cn=$1 -b ${groupsRdn},${ldap_base_dn})
	if [ -z "$t" ]; then
		echo "Groupe $1 invalide"
	else
		groupe=$1
		interactif="n"
	fi
fi

while [ -z "$groupe" ]
do
	# Choix du groupe
	echo ""
	echo "Voici la liste des groupes existants: "
	ldapsearch -xLLL -b ${groupsRdn},${ldap_base_dn} cn|grep "^cn: "| sed -e "s|^cn:||" | sort | tr -d '\n' | more

	echo ""
	echo -e "Choix du groupe: \c"
	read -e groupe

	if [ -n "$groupe" ]; then
		t=$(ldapsearch -xLLL cn=$groupe -b ${groupsRdn},${ldap_base_dn})
		if [ -z "$t" ]; then
			echo "Groupe $groupe invalide"
			groupe=""
		fi
	fi
done

t=$(ldapsearch -xLLL -b ${groupsRdn},${ldap_base_dn} "(&(cn=$groupe)(objectClass=posixGroup))")
if [ $n "$t" ]; then
	# C'est un posixGroup
	if [ "$interactif" = "y" ]; then
		echo ""
		echo "Liste des membres du groupe $groupe:"
		ldapsearch -xLLL cn=$groupe -b ${groupsRdn},${ldap_base_dn} memberUid | grep "^memberUid: " | sed -e "s|^memberUid: ||" | more
	else
		ldapsearch -xLLL cn=$groupe -b ${groupsRdn},${ldap_base_dn} memberUid | grep "^memberUid: " | sed -e "s|^memberUid: ||"
	fi
else
	# Traiter aussi le cas groupOfNames
	echo "Cas groupOfNames a implementer."
	exit
fi
