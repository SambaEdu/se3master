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
	echo "Script destine a retourner la liste des membres d'un parc (cn)."
	echo ""
	echo "USAGE: Passer en parametre le nom du parc."
	echo "       Sinon, sans parametre, la liste des parcs est proposee."
	echo ""
	echo "       A IMPLEMENTER:"
	echo "       Si un parc est passe en parametre, on peut donner ensuite,"
	echo "       la liste des attributs a recuperer."
	exit
fi

interactif="y"

parc=""
if [ -n "$1" ]; then
	t=$(ldapsearch -xLLL cn=$1 -b ${parcsRdn},${ldap_base_dn})
	if [ -z "$t" ]; then
		echo "Parc $1 invalide"
	else
		parc=$1
		interactif="n"
	fi
fi

avec_ip="n"
t=$(echo "$*" | egrep -i "( ip$| ip | ipHostNumber$| ipHostNumber )")
if [ -n "$t" ]; then
	avec_ip="y"
fi

avec_mac="n"
t=$(echo "$*" | egrep -i "( mac$| mac | macAddress$| macAddress )")
if [ -n "$t" ]; then
	avec_mac="y"
fi

while [ -z "$parc" ]
do
	# Choix du parc
	echo ""
	echo "Voici la liste des parcs existants: "
	ldapsearch -xLLL -b ${parcsRdn},${ldap_base_dn} cn|grep "^cn: "| sed -e "s|^cn:||" | sort | tr -d '\n' | more

	echo ""
	echo -e "Choix du parc: \c"
	read -e parc

	if [ -n "$parc" ]; then
		t=$(ldapsearch -xLLL cn=$parc -b ${parcsRdn},${ldap_base_dn})
		if [ -z "$t" ]; then
			echo "Parc $parc invalide"
			parc=""
		fi
	fi
done

if [ "$interactif" = "y" ]; then
	echo ""
	echo "Liste des machines ou imprimantes du parc $parc:"
	ldapsearch -xLLL cn=$parc -b ${parcsRdn},${ldap_base_dn} member | grep "^member: " | sed -e "s|^member: cn=||"|cut -d"," -f1 | more
else
	opt=""
	if [ "$avec_ip" = "y" ]; then
		opt="$opt ipHostNumber"
	fi
	if [ "$avec_mac" = "y" ]; then
		opt="$opt macAddress"
	fi

	if [ -n "$opt" ]; then
		ldapsearch -xLLL cn=$parc -b ${parcsRdn},${ldap_base_dn} member | grep "^member: " | sed -e "s|^member: cn=||"|cut -d"," -f1|while read cn
		do
			ldapsearch -xLLL -b ${computersRdn},${ldap_base_dn} cn=$cn $opt
		done
	else
		ldapsearch -xLLL cn=$parc -b ${parcsRdn},${ldap_base_dn} member | grep "^member: " | sed -e "s|^member: cn=||"|cut -d"," -f1
	fi
fi
