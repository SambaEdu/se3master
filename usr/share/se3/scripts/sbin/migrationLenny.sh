#!/bin/bash

#### ATTENTION
#	Ne gere pas encore les comptes de Trash
###
. /usr/share/se3/includes/config.inc.sh -cml

# On cherche l'ip machine courante
ldapsearch -xLLL -D $adminRdn,$ldap_base_dn -w $adminPw objectClass=person uid| grep uid:| cut -d ' ' -f2| while read uid
do
		(
		echo "dn: uid=$uid,$peopleRdn,$ldap_base_dn"
		echo "changetype: modify"
		echo "replace: sambaPwdLastSet"
		echo "sambaPwdLastSet: 1"
		) | ldapmodify -x -D $adminRdn,$ldap_base_dn -w $adminPw >/dev/null 2>&1
		if [ "$?" != "0" ]
		then
			#corbeille
			  (
	                echo "dn: uid=$uid,ou=Trash,$ldap_base_dn"
	                echo "changetype: modify"
	                echo "replace: sambaPwdLastSet"
	                echo "sambaPwdLastSet: 1"
	                ) | ldapmodify -x -D $adminRdn,$ldap_base_dn -w $adminPw >/dev/null

		fi
done
