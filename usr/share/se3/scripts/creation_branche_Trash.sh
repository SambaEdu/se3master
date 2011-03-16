#!/bin/bash

#
## $Id$ ##
#
##### Script destine a creer la branche Trash si elle n'existe pas - Stephane Boireau #####
#

. /usr/share/se3/includes/config.inc.sh -l

t=$(ldapsearch -xLLL ou=Trash)
if [ -n "$t" ]; then
	echo "La branche Trash existe deja."
else
	mkdir -p /root/tmp
	echo "dn: ou=Trash,$ldap_base_dn
objectClass: organizationalUnit
ou: Trash
" > /root/tmp/creation_ou_Trash.ldif
	ldapadd -x -D $adminRdn,$ldap_base_dn -w $adminPw -f /root/tmp/creation_ou_Trash.ldif
fi
