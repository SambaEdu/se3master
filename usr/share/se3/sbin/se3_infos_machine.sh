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
	echo "Script destine a retourner la liste des infos concernant une machine."
	echo ""
	echo "USAGE: Passer en parametre le nom d'une machine."
	echo ""
	exit
fi

interactif="y"

machine=""
if [ -n "$1" ]; then
	t=$(ldapsearch -xLLL cn=$1 -b ${computersRdn},${ldap_base_dn})
	if [ -z "$t" ]; then
		echo "Machine $1 invalide"
	else
		machine=$1
		interactif="n"
	fi
fi

while [ -z "$machine" ]
do
	# Choix d'une machine
	echo ""
	echo "Voici la liste des machines existantes: "
	ldapsearch -xLLL -b ${computersRdn},${ldap_base_dn} cn|grep "^cn: "| sed -e "s|^cn:||" | sort | uniq | tr -d '\n' | more

	echo ""
	echo -e "Choix d'une machine: \c"
	read -e machine

	if [ -n "$machine" ]; then
		t=$(ldapsearch -xLLL cn=$machine -b ${computersRdn},${ldap_base_dn})
		if [ -z "$t" ]; then
			echo "Machine $machine invalide"
			machine=""
		fi
	fi
done

ipHostNumber=$(ldapsearch -xLLL cn=$machine -b ${computersRdn},${ldap_base_dn} ipHostNumber | grep "^ipHostNumber: " |sed -e "s|ipHostNumber: ||")
macAddress=$(ldapsearch -xLLL cn=$machine -b ${computersRdn},${ldap_base_dn} macAddress | grep "^macAddress: " |sed -e "s|macAddress: ||")
echo "machine =      $machine"
echo "ipHostNumber = $ipHostNumber"
echo "macAddress =   $macAddress"
