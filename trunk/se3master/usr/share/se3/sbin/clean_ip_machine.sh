#!/bin/bash

## $Id$ ##
#
# recup parametres mysql
. /etc/se3/config_o.cache.sh

# recup parametres ldap
. /etc/se3/config_l.cache.sh

# Dossier dans lequel creer les fichiers LDIF temporaires de correction
tmp=/var/lib/se3/connexion_ldif
mkdir -p ${tmp}
# Fichier des modifs LDAP
ldif_modif=$tmp/clean_$RANDOM.ldif
# La creation d'un fichier est source de lenteur... cela dit, on ne fait normalement pas la modif de l'annuaire frequemment.

# Recherche LDAP de la machine dans la branche ou=Computers
# ---------------------------------------------------------

ldapsearch -xLLL -b ${computersRdn},${ldap_base_dn} "(registeredAddress=*)" registeredAddress | sed -e "s/^registeredAddress:/changetype: modify\ndelete: registeredAddress\nregisteredAddress:/g" > $ldif_modif
ldapmodify -x -c -D ${adminRdn},${ldap_base_dn} -w ${adminPw} -f ${ldif_modif}  > /dev/null 2>&1
touch /tmp/csvtodo
exit 0

