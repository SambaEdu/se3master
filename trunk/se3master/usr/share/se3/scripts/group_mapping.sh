#!/bin/bash

#
##### Script de mapping de groupe #####
#
# Auteur: Olivier lacroix
#
## $Id$ ##
#
# /usr/share/se3/scripts/group_mapping.sh


if [ "$1" = "--help" -o "$1" = "-h" -o $# -eq 0 -o $# -gt 3 ]; then
	echo "Script permettant de mapper un groupe, lance à partir de l'interface Annuaire"
	echo ""
	echo "Usage : Avec 3 parametres : le nom du groupe à mapper (Posix Group), le nom nt  et la description "
	echo "        Avec 1 ou 2 parametres : les autre sont recuperes dans ldap"
	echo " /usr/share/se3/scripts/group_mapping.sh Classe_2nde1"
	exit
fi

WWWPATH="/var/www"

## recuperation des variables necessaires pour interoger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 | cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut	-d = -f 2 | cut -d \" -f 2`
 	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
 	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
	ERREUR "Fichier de configuration inaccessible, le script ne peut se poursuivre."
	
fi

	### recuperation des parametres actuels de l'annuaire dans la base ####
	BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

	TST_GRP_POSIX=$(ldapsearch -xLLL "cn=$1" | grep -i posixGroup)
#echo "resultat recherche LDAP : $TST_GRP_POSIX"
if [ -z "$TST_GRP_POSIX" ]; then
  echo "Groupe absent de l'annuaire LDAP ou de type \"Group of name\" : le mapping de groupe n'est pas possible avec ces derniers."
else
  TST_MAPPING=$(ldapsearch -xLLL "cn=$1" | grep -i sambaGroupMapping)
  if [ -z "$TST_MAPPING" ]; then
    if [ -z "$2" ]; then
    	ntgroup=$1
    else
    	ntgroup=$2
    fi
    if [ -z "$3" ]; then
    	description=$(ldapsearch -xLLL "cn=$1" | grep description | sed "s/description: //")
    else
    	description="$3"
    fi
   
    net groupmap add ntgroup=$ntgroup unixgroup=$1 type=domain comment="$description" 1>/dev/null
    echo "Mapping du groupe $1 effectue"
  else
    echo "Le groupe $1 est déjà mappe dans l'annuaire"
  fi
fi
