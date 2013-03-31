#!/bin/bash
#
## $Id$ ##
#
##### Script de modification d'attributs LDAP - stéphane Boireau #####

if [ -z "$3" -o "$1" = "-h" -o "$1" = "--help" ]; then
	echo "Script de modification d'attributs LDAP"
	echo ""
	echo "USAGE: $0 entree_a_modifier=valeur ou=branche attribut=valeur"
	echo "       Exemple: $0 uid=toto ou=People sn=Tot"
	echo "                changera l'attribut sn de uid=toto dans la branche People."
	exit
fi

. /etc/se3/config_l.cache.sh

dest_svg=/var/se3/save/svg_modif_ldap
mkdir -p ${dest_svg}
ladate=$(date "+%Y%m%d_%H%M%S")

motif=$1
motif_modif=$(echo "${motif}" | tr "=" "_")

branche=$2

attribut=$(echo "$3"|cut -d"=" -f1)
valeur=$(echo "$3"|cut -d"=" -f2)

ldapsearch -xLLL -D ${adminRdn},${ldap_base_dn} -w ${adminPw} ${motif} > ${dest_svg}/${motif_modif}_${ladate}.ldif
if [ "$?" != "0" ]; then
	echo "Abandon: La sauvegarde a echoue."
	exit
fi

echo "dn: ${motif},${branche},${ldap_base_dn}
changetype: modify
replace: ${attribut}
${attribut}: ${valeur}
" > ${dest_svg}/modif_${motif_modif}_${ladate}.ldif

ldapmodify -x -D ${adminRdn},${ldap_base_dn} -w ${adminPw} -f ${dest_svg}/modif_${motif_modif}_${ladate}.ldif
if [ "$?" != "0" ]; then
	echo "Echec"
	exit 1
else
	echo "Succes"
	exit 0
fi
