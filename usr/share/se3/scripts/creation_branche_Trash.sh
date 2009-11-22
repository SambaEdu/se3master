#!/bin/sh

#
## $Id$ ##
#
##### Script destine a creer la branche Trash si elle n'existe pas - Stephane Boireau #####
#

. /usr/share/se3/sbin/variables_admin_ldap.sh lib > /dev/null

# Si le variables_admin_ldap.sh n'est pas assez recent
if [ -z "$BASEDN" -o -z "$ROOTDN" -o -z "$PASSDN" ]; then
	# On utilise les parametres locaux... en esperant que le ldap est bien local
	BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
	ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
	PASSDN=$(cat /etc/ldap.secret)
fi

t=$(ldapsearch -xLLL ou=Trash)
if [ -n "$t" ]; then
	echo "La branche Trash existe deja."
else
	mkdir -p /root/tmp
	echo "dn: ou=Trash,$BASEDN
objectClass: organizationalUnit
ou: Trash
" > /root/tmp/creation_ou_Trash.ldif
	ldapadd -x -D $ROOTDN -w $PASSDN -f /root/tmp/creation_ou_Trash.ldif
fi
