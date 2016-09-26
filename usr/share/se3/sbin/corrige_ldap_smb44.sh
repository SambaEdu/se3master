#!/bin/bash

# Script destiné à remettre d'aplomb les comptes admin et root au niveau attributs ldap.
# Auteur: Franck molle 
# Dernière modification: /09/2016


. /etc/se3/config_l.cache.sh

# /usr/share/se3/includes/config.inc.sh -lm

BASEDN="$ldap_base_dn"
ADMINRDN="$adminRdn"
ADMINPW="$adminPw"
PEOPLERDN="$peopleRdn"
GROUPSRDN="$groupsRdn"
RIGHTSRDN="$rightsRdn"

testgecos_adm=$(ldapsearch -xLLL uid=admin gecos | grep -v "dn:")
if [ -z "$testgecos_adm" ]; then
ldapmodify -x -v -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF
dn: uid=admin,$PEOPLERDN,$BASEDN
changetype: modify
add: givenName
givenName: Admin
-
add: initials
initials: Admin
-
add: gecos
gecos: Administrateur  Se3,,,
EOF
fi

testgecos_root=$(ldapsearch -xLLL uid=root gecos | grep -v "dn:")
if [ -z "$testgecos_root" ]; then
ldapmodify -x -v -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF
dn: cn=root,$BASEDN
changetype: modify
add: gecos
gecos: Root samba Se3,,,
EOF
fi
exit 0