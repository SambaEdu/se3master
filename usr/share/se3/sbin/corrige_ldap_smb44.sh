#!/bin/bash

# Script destiné à remettre d'aplomb les comptes admin et root au niveau attributs ldap.
# Auteur: Franck molle 
# Dernière modification: /09/2016
. /etc/se3/config_c.cache.sh
. /etc/se3/config_m.cache.sh
. /etc/se3/config_l.cache.sh

BASEDN="$ldap_base_dn"
ADMINRDN="$adminRdn"
ADMINPW="$adminPw"
PEOPLERDN="$peopleRdn"
GROUPSRDN="$groupsRdn"
RIGHTSRDN="$rightsRdn"

echo "Remise en place di besoin des mapping de groupes"
net groupmap add sid=$domainsid-512 ntgroup=Admins unixgroup=admins type=domain comment="Administrateurs du domaine"
net groupmap add ntgroup=Eleves unixgroup=Eleves type=domain comment="Eleves du domaine"
net groupmap add ntgroup=Profs unixgroup=Profs type=domain comment="Profs du domaine"
net groupmap add ntgroup="Utilisateurs du domaine" rid="513" unixgroup="lcs-users" type="domain"
net groupmap add ntgroup="machines" rid="515" unixgroup="machines" type="domain"


testgecos_adm=$(ldapsearch -xLLL uid=admin gecos | grep '^gecos: ')
if [ -z "$testgecos_adm" ]; then
	echo "Correction des attributs du compte admin"
	ldapmodify -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF
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

testoldroot=$(ldapsearch -xLLL -b cn=root,$BASEDN uid | grep 'uid: root')
if [ -n "$testoldroot" ]; then
	echo "Suppression compte root samba obsolete"
	ldapdelete -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" "cn=root,$BASEDN"
fi



testgrouproot=$(ldapsearch -xLLL -b cn=root,$GROUPSRDN,$BASEDN cn | grep 'cn: root')
if [ -n "$testgrouproot" ]; then
	ldapdelete -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" "cn=root,$GROUPSRDN,$BASEDN"
fi	
echo "Modification du groupe root samba "
ldapadd -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF
dn: cn=root,$GROUPSRDN,$BASEDN
objectClass: posixGroup
gidNumber: 0
cn: root
memberUid: root
EOF

net groupmap add ntgroup=Roots unixgroup=root type=domain comment="Roots"




testnewroot=$(ldapsearch -xLLL uid=root -b $PEOPLERDN,$BASEDN uid | grep 'uid: root')
if [ -z "$testnewroot" ]; then
	echo "Mise à jour compte root samba"
	ldapadd -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF
dn: uid=root,$PEOPLERDN,$BASEDN
uid: root
sn: Se3
cn: root Se3
gecos: root Se3,,,
mail: root@$domain
loginShell: /bin/true
objectClass: top
objectClass: posixAccount
objectClass: shadowAccount
objectClass: person
objectClass: inetOrgPerson
objectClass: sambaSamAccount
uidNumber: 0
sambaPwdMustChange: 2147483647
gidNumber: 0
shadowLastChange: 1468229295
homeDirectory: /root
sambaSID: $domainsid-0
sambaPrimaryGroupSID: $domainsid-0
sambaLMPassword: FFB67A52AC531164AAD3B435B51404EE
sambaNTPassword: 538388DFE2BF2556833682EABF77CB10
sambaPasswordHistory: 00000000000000000000000000000000000000000000000000000000
 00000000
userPassword:: e1NTSEF9UjYrYVpLZGU2RmVnak5uZGRENll4SWxualBIcDcxVis=
sambaPwdLastSet: 1
sambaAcctFlags: [DU         ]
EOF
fi

testsambadomain=$(ldapsearch -xLLL objectClass=sambaDomain sambaDomainName | grep '^sambaDomainName: ')
if [ -z "$testsambadomain" ]; then
	echo "Ajout de l'entrée sambaDomainName dans l'annuaire"
ldapadd -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF
dn: sambaDomainName=$se3_domain,$BASEDN
sambaAlgorithmicRidBase: 1000
gidNumber: 1000
uidNumber: 1000
objectClass: sambaDomain
objectClass: sambaUnixIdPool
sambaSID: $domainsid
sambaDomainName: $se3_domain
sambaLockoutThreshold: 0
sambaMinPwdAge: 0
sambaRefuseMachinePwdChange: 0
sambaMinPwdLength: 5
sambaLogonToChgPwd: 0
sambaForceLogoff: -1
sambaLockoutDuration: 30
sambaLockoutObservationWindow: 30
sambaMaxPwdAge: -1
sambaPwdHistoryLength: 0
sambaNextRid: 6752
EOF
fi

echo "Modification si besoin des attributs samba pour les utilisateurs"
ldapsearch -xLLL -D $adminRdn,$ldap_base_dn -b $PEOPLERDN,$BASEDN -w $adminPw objectClass=person uid| grep uid:| cut -d ' ' -f2| grep -v "^root$\|^nobody$\|^admin$" | while read uid
do
# cat > /tmp/t.ldif <<EOF
ldapmodify -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF  >/dev/null
dn: uid=$uid,$peopleRdn,$ldap_base_dn
changetype: modify
replace: sambaPrimaryGroupSID
sambaPrimaryGroupSID: $domainsid-513
-
replace: sambaPwdLastSet
sambaPwdLastSet: 1
EOF
done

echo "Modification si besoin des attributs samba pour admin"
ldapmodify -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" <<EOF
dn: uid=admin,$PEOPLERDN,$BASEDN
changetype: modify
replace: sambaPrimaryGroupSID
sambaPrimaryGroupSID: $domainsid-512
-
replace: sambaSID
sambaSID: $domainsid-500
-
EOF

# 
exit 0