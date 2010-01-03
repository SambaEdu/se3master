#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj120

#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)

### recup pass root mysql
. /root/.my.cnf 2>/dev/null
. /usr/share/se3/includes/config.inc.sh -lm
. /usr/share/se3/includes/functions.inc.sh


MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail à l'admin
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 120" $MAIL_ADMIN
fi
}


LDAPIP="$ldap_server"
BASEDN="$ldap_base_dn"
ADMINRDN="$adminRdn"
ADMINPW="$adminPw"
PEOPLERDN="$peopleRdn"
GROUPSRDN="$groupsRdn"
RIGHTSRDN="$rightsRdn"

PEOPLER=`echo $PEOPLERDN |cut -d = -f 2`
RIGHTSR=`echo $RIGHTSRDN |cut -d = -f 2`
GROUPSR=`echo $GROUPSRDN |cut -d = -f 2`

# Correction nss pour ignorer root et openldap
[ -z "$(grep "nss_initgroups_ignoreusers" /etc/libnss-ldap.conf)" ] && echo "nss_initgroups_ignoreusers root,openldap,plugdev,disk,kmem,tape,audio,daemon,lp,rdma,fuse,video,dialout,floppy,cdrom,tty" >> /etc/libnss-ldap.conf

# echo  "Ajout des groupes Samba3 obligatoires dans LDAP"
DOMAINSID=`net getlocalsid | cut -d: -f2 | sed -e "s/ //g"`
cat ldif/Samba.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#DOMAINSID#/$DOMAINSID/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | sed -e "s/#PEOPLE#/$PEOPLER/g" > /root/Samba_maj120.ldif 
# ldapadd -x -c -D "$ADMINRDN,$BASEDN" -w $ADMINPW -f /root/Samba_maj120.ldif

#Change le SambaPrimaryGroupe
echo "Modification du SambaPrimaryGroupe en arriere plan dans 2mn"
AT_SCRIPT=/root/modif_SambaPrimaryGroupe.sh
echo "#!/bin/bash
ldapsearch -x -b $PEOPLERDN,$BASEDN '(objectclass=*)' uid | grep -v People | grep -v \# | grep uid: | while read A
do

ID=`echo \$A | cut -d : -f 2 | cut -b 2-`
        if [ "\$ID" != "" ]
        then
ldapmodify -x -v -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" > /dev/null 2>&1 <<EOF
dn: uid=\$ID,$PEOPLERDN,$BASEDN
changetype: modify
replace: sambaPrimaryGroupSID
sambaPrimaryGroupSID: $DOMAINSID-513
EOF

	fi

done
" >$AT_SCRIPT
chmod 700 $AT_SCRIPT
at now +2 minutes -f $AT_SCRIPT >/dev/null

echo  "Ajout des groupes Samba3 obligatoires dans LDAP"
# mappage de nobody, root, etc...
ldapsearch -xLLL "(&(sambaprimarygroupsid=S-1-5-32-546)(uid=nobody)(sambasid=$DOMAINSID-501))" cn | grep nobody > /dev/null 2>&1 || \
echo "dn: uid=nobody,$PEOPLERDN,$BASEDN
cn: nobody
uid: nobody
description: samba guest domain account
gecos: samba guest domain account
loginShell: /bin/false
sambaAcctFlags: [NU         ]
sambaPwdMustChange: 2147483647
sambaPwdCanChange: 0
sambaKickoffTime: 2147483647
sambaLogoffTime: 2147483647
sambaLogonTime: 0
sambaPwdLastSet: 0
homeDirectory: /dev/null
objectClass: inetOrgPerson
objectClass: sambaSamAccount
objectClass: posixAccount
objectClass: shadowAccount
objectClass: organizationalPerson
objectClass: top
objectClass: person
sn: nobody
gidNumber: 65534
sambaPrimaryGroupSID: S-1-5-32-546
uidNumber: 65534
sambaSID: $DOMAINSID-501" | ldapadd -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW

ldapsearch -xLLL "(&(sambasid=S-1-5-32-546)(objectclass=posixgroup))" cn | grep nogroup > /dev/null 2>&1 || \
echo "dn: cn=nogroup,$GROUPSRDN,$BASEDN
sambaGroupType: 2
description: le groupe fantome
objectClass: posixGroup
objectClass: top
objectClass: sambaGroupMapping
gidNumber: 65534
sambaSID: S-1-5-32-546
displayName: nogroup
cn: nogroup
memberUid: nobody" | ldapadd -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW 

ldapsearch -xLLL "cn=root -b $GROUPSRDN,$BASEDN" cn | grep root > /dev/null 2>&1 || \
echo "dn: cn=root,$GROUPSRDN,$BASEDN
objectClass: posixGroup
objectClass: sambaGroupMapping
gidNumber: 0
cn: root
description: groupe root samba
memberUid: root
sambaGroupType: 4
sambaSID: $DOMAINSID-0
displayName: Roots" | ldapadd -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW

ldapsearch -xLLL "(&(sambasid=$DOMAINSID-514)(objectclass=posixgroup))" cn | grep invites > /dev/null 2>&1 || \
echo "dn: cn=invites,$GROUPSRDN,$BASEDN
objectClass: posixGroup
objectClass: sambaGroupMapping
gidNumber: 999
cn: invites
description: Invites du domaine
memberUid: nobody
sambaGroupType: 4
sambaSID: $DOMAINSID-514
displayName: Invites" | ldapadd -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW 


# mappage des groupes particuliers
echo "Mapping des groupes particuliers"

# groupe lcs-users mappé vers "utilisateurs du domaine -513"
net groupmap list | grep "lcs-users"  > /dev/null 2>&1 || net groupmap add ntgroup="Utilisateurs du domaine" rid="513" unixgroup="lcs-users" type="domain"

# groupe machines mappé vers -515
net groupmap list | grep "machines"  > /dev/null 2>&1 || net groupmap add ntgroup="machines" rid="515" unixgroup="machines" type="domain"

# groupe admins mappé vers -512
net groupmap list | grep "admins"  > /dev/null 2>&1 || net groupmap  add ntgroup="Admins" rid="512" unixgroup="admins" type="domain"

# groupe profs / eleves rid auto
net groupmap list | grep "Profs"  > /dev/null 2>&1 || net groupmap add ntgroup="Profs" unixgroup="Profs" type="domain" comment="Profs du domaine"
net groupmap list | grep "Eleves"  > /dev/null 2>&1 || net groupmap add ntgroup="Eleves" unixgroup="Eleves" type="domain" comment="Eleves du domaine"


# mise a jour des parametres caches pour le domaine si besoin ( remplacement confse3.ini )
echo " Remplacement du fichier confse3.ini par des parametres dans la base se3db"

if [ -z "$se3_domain" ]; then 
    eval $(grep "workgroup =" /etc/samba/smb.conf | sed "s/ //g")
    SETMYSQL se3_domain "$workgroup" "Nom du domaine windows" 4
fi
if [ -z "$netbios_name" ]; then 
    eval $(grep "netbios name =" /etc/samba/smb.conf | sed "s/ //g")
    SETMYSQL netbios_name "$netbiosname" "Nom netbios du serveur" 4
    netbios_name=$netbiosname
fi
if [ -z "$se3ip" ]; then 
    se3ip=$(nmblookup $netbios_name | grep "$netbios_name<00>" | cut -d " " -f1)
    SETMYSQL se3ip "$se3ip" "Adresse IP du serveur" 4
fi


echo "mise en place des privileges samba"
echo "mise a jour de confse3.ini"
# 
CONFSE3="/var/se3/Progs/install/installdll/confse3.ini"
CONFSE3_SAV="/var/se3/Progs/install/installdll/confse3.ini-root"

# Sauvegarde fichier avant modif si besoin et init variable smbpass
if [ -f /var/se3/Progs/install/installdll/confse3.ini-root ]; then
    SMBPASS=`grep "password_ldap_domain" "$CONFSE3_SAV" | sed -e 's/^$//'| sed -e 's/\r//' |cut -d= -f2`
    net -U adminse3%"$SMBPASS" rpc rights grant adminse3 SeMachineAccountPrivilege SePrintOperatorPrivilege
else

	ldapsearch -xLLL cn=root -b $BASEDN cn | grep "cn=root" > /dev/null 2>&1 || \
	echo "dn: cn=root,$BASEDN
cn: root
description: compte root samba 
gecos: samba root  account
loginShell: /bin/false
sambaAcctFlags: [NU         ]
sambaPwdMustChange: 2147483647
sambaPwdCanChange: 0
sambaKickoffTime: 2147483647
sambaLogoffTime: 2147483647
sambaLogonTime: 0
sambaPwdLastSet: 0
homeDirectory: /dev/null
objectClass: inetOrgPerson
objectClass: sambaSamAccount
objectClass: posixAccount
objectClass: shadowAccount
objectClass: organizationalPerson
objectClass: top
objectClass: person
uidNumber: 0
uid: root
sn: root
gidNumber: 0
sambaPrimaryGroupSID: $DOMAINSID-0
sambaSID: $DOMAINSID-1" | ldapadd -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW 

    SMBPASS=`grep "password_ldap_domain" "$CONFSE3" | sed -e 's/^$//'| sed -e 's/\r//' |cut -d= -f2`
    # Creation compte adminse3 dans annuaire si besoin est 
    [ -z "$(ldapsearch -xLLL uid=adminse3)" ] && /usr/share/se3/sbin/create_adminse3.sh
    net -U root%"$SMBPASS" rpc rights grant admin SeMachineAccountPrivilege SePrintOperatorPrivilege
    net -U root%"$SMBPASS" rpc rights grant adminse3 SeMachineAccountPrivilege SePrintOperatorPrivilege
    if [ $? -eq 0 ]; then 
	  cp $CONFSE3 $CONFSE3_SAV
	  sed -i  "s/compte_ldap_domain=root/compte_ldap_domain=adminse3/" $CONFSE3
	  sed -i "/password_ldap_domain=/d" $CONFSE3 
	  echo -e "password_ldap_domain=$xppass\r" >> $CONFSE3
	  ldapdelete -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW cn=root,$BASEDN
	  echo "Attention, la mise au domaine se fait maintenant avac le compte adminse3.
Le compte root samba est maintenant desactive"
	  echo "ATTENTION A VERIFIER
la mise en pace des privileges d administration peut causer des problemes 
avec les pilotes d imprimantes si vous constatez que les pilotes ne fonctionnent plus,
c est qu il faut les uploader a nouveau sur le serveur (voir la procedure de la doc)"
    fi
fi

#Maj scripts clients linux
#### A intégrer dans se3-domain !!! ###
#echo "Maj des scripts pour clients linux"
#/usr/share/se3/sbin/create_client_linux.sh >/dev/null 
#echo "Les scripts clients linux sont disponibles dans /root
#Voir la documentation pour plus d'infos"

# on finit par l'actualisation du cache des parametres : 

/usr/share/se3/includes/config.inc.sh -clpbmsdf 


echo "Mise a jour 120:
- Ajout nouvelle architecture de connexion
- Ajout adminse3 dans l'annuaire par defaut
- Ajout corbeille reseau
- Ajout integration vista
- Ajout des groupes Domain Users et Domain Guests
- Modification du sambaPrimaryGroupSID
- ajout des privileges mise au domaine adminse3, abandon de root samba
- Migration vers nouveau code de gestion de restrictions
- correction des scripts imprimantes pour ne donner les droits que pour adminse3" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0
