#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj112



#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)

### recup pass root mysql
. /root/.my.cnf 2>/dev/null

MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail à l'admin
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 112" $MAIL_ADMIN
fi
}


POURSUIVRE()
{
	[ -n "$1" ] && echo "$1"
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "O" -a "$REPONSE" != "n" ]
	do
		#echo -e "$COLTXT"
		echo -e "${COLTXT}Peut-on poursuivre ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done
	echo -e "$COLTXT"
	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		echo "Abandon!"
		exit 1
	fi
}

# Recuperation de variables LDAP
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
	echo "Impossible d'accéder au paramètre BASEDN"
	exit 1
fi
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINRDN" ]; then
	echo "Impossible d'accéder au paramètre ADMINRDN"
	exit 1
fi
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINPW" ]; then
	echo "Impossible d'accéder au paramètre ADMINPW"
	exit 1
fi
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PEOPLERDN" ]; then
	echo "Impossible d'accéder au paramètre PEOPLERDN"
	exit 1
fi
GROUPSRDN=`echo "SELECT value FROM params WHERE name='groupsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$GROUPSRDN" ]; then
	echo "Impossible d'accéder au paramètre GROUPSRDN"
	exit 1
fi
RIGHTSRDN=`echo "SELECT value FROM params WHERE name='rightsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$RIGHTSRDN" ]; then
	echo "Impossible d'accéder au paramètre RIGHTSRDN"
	exit 1
fi
DOMAINSID=`echo "SELECT value FROM params WHERE name='domainSID'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$DOMAINSID" ]; then
        echo "Impossible d'acc?der au param?tre DOMAINSID"
        exit 1
fi
XPPASS=`echo "SELECT value FROM params WHERE name='xppass'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$XPPASS" ]; then
        echo "Impossible d'acc?der au parametre XPPASS"
        exit 1
fi
LDAPIP=`echo "SELECT value FROM params WHERE name='ldap_server'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$LDAPIP" ]; then
        echo "Impossible d'acc?der au param?tre BASEDN"
        exit 1
fi

PEOPLER=`echo $PEOPLERDN |cut -d = -f 2`
RIGHTSR=`echo $RIGHTSRDN |cut -d = -f 2`
GROUPSR=`echo $GROUPSRDN |cut -d = -f 2`

# echo  "Ajout des groupes Samba3 obligatoires dans LDAP"
DOMAINSID=`net getlocalsid | cut -d: -f2 | sed -e "s/ //g"`
cat ldif/Samba.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#DOMAINSID#/$DOMAINSID/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | sed -e "s/#PEOPLE#/$PEOPLER/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW

# Change le SambaPrimaryGroupe
ldapsearch -x -b $PEOPLERDN,$BASEDN '(objectclass=*)' uid | grep -v People | grep -v \# | grep uid: | while read A
do

ID=`echo $A | cut -d : -f 2 | cut -b 2-`
        if [ "$ID" != "" ]
        then
ldapmodify -x -v -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" > /dev/null 2>&1 <<EOF
dn: uid=$ID,$PEOPLERDN,$BASEDN
changetype: modify
replace: sambaPrimaryGroupSID
sambaPrimaryGroupSID: $DOMAINSID-513
EOF

fi

done

# Mise a jour de la conf sudo (voir commit rev 3956)
cp -a conf/sudoers /etc
chmod 0440 /etc/sudoers

# mappage de nobody, root, etc...
ldapsearch -xLLL "(&(sambaprimarygroupsid=S-1-5-32-546)(uid=nobody)(sambasid=$DOMAINSID-501))" cn | grep nobody > /dev/null 2>&1 || \
ldapdelete -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW uid=nobody,$PEOPLERDN,$BASEDN && \
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
sambaSID: $DOMAINSID-501" | ldapadd -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW && \\
echo "nobody cree"

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
memberUid: nobody" | ldapadd -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW && \
echo "nogroup cree"


ldapsearch -xLLL "(&(sambaprimarygroupsid=$DOMAINSID-0)(uid=root)(sambasid=$DOMAINSID-1))" cn | grep root > /dev/null 2>&1 || \
echo "dn: uid=root,$PEOPLERDN,$BASEDN
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

ldapsearch -xLLL "(&(sambasid=$DOMAINSID-0)(objectclass=posixgroup))" cn | grep root > /dev/null 2>&1 || \
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

ldapdelete -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW cn=root,$BASEDN

echo "on mappe les groupes particuliers"

# mappage des groupes particuliers

# groupe lcs-users mappé vers "utilisateurs du domaine -513"
#net groupmap list | grep "lcs-users"  > /dev/null 2>&1 || net groupmap add ntgroup="Utilisateurs du domaine" rid="513" unixgroup="lcs-users" type="domain"
# groupe machines mappé vers -515
net groupmap list | grep "machines"  > /dev/null 2>&1 || net groupmap add ntgroup="machines" rid="515" unixgroup="machines" type="domain"
# groupe admins mappé vers -512
net groupmap list | grep "admins"  > /dev/null 2>&1 || net groupmap  add ntgroup="Admins" rid="512" unixgroup="admins" type="domain"


echo "on mappe tous les groupes"
echo "voulez vous supprimer les groupes cours ?
si vous ne les utilisez pas, le gain de performance peut etre important. 
ATTENTION 
pour les serveurs en Sarge, un utilisateur ne peut appartenir a plus de 32 groupes
Il est donc conseille de supprimer les cours.
Pour Etch cette limite n'existe pas
o/N"
read rep
if [ "$rep" == "o" ]; then 
	ldapsearch -xLLL -h $LDAPIP -b $GROUPSRDN,$BASEDN "(cn=Cours_*)" dn | sed "s/dn: //g" | \
		 ldapdelete -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW
	echo " Groupes cours supprimés"
fi

for group in $(ldapsearch -xLLL -h $LDAPIP -b $GROUPSRDN,$BASEDN "(&(objectClass=posixGroup)(!(objectClass=sambaGroupMapping)))" cn | grep "^cn:"  | sed "s/cn: //"); do

	description=$(ldapsearch -xLLL -h $LDAPIP -b $GROUPSRDN,$BASEDN "(&(objectClass=posixGroup)(!(objectClass=sambaGroupMapping)))" description | grep "^description:"  | sed "s/description: //")
	echo "mappage de $group" #>>/tmp/addposix.ldif
	/usr/share/se3/scripts/group_mapping.sh $group $group $description 
done

echo "Mise a jour du smb.conf"
SMBCONF=/etc/samba/smb.conf
test=true
grep -i "ldapsam:trusted = yes" $SMBCONF && test=false

if [ $test = true ]; then
	mv $SMBCONF $SMBCONF.before.printer
	global=false
	printc=false
	grep -i "ldapsam:trusted = yes" $SMBCONF && test=false
	cat $SMBCONF.before.printer | while ($test)
	do
	        read ligne || test=false
	        echo $ligne | grep -qi "ldap admin dn" && printc=true 
	        if [ $printc = true ]; then
	                echo -e "\tldapsam:trusted = yes" >> $SMBCONF
	                echo -e "\tenable privileges = yes" >> $SMBCONF
	                echo -e "\t$ligne" >>$SMBCONF
	                printc=false
	        else
	                echo $ligne | grep -q "\[" && global=true
	                echo $ligne | grep -q "#" && global=true
	                if [ $global = true ]; then
	                        echo "$ligne" >>$SMBCONF
	                        global=false
	                else
	                        echo -e "\t$ligne" >>$SMBCONF
	                fi
	        fi
	done
	sed -i -e "/printer admin = /d"  $SMBCONF
fi

echo "on redemarre samba..."
/etc/init.d/samba restart


echo "mise en place des privileges samba"
echo "mise a jour de confse3.ini"

echo "entrez le mot de passe admin de SE3 :"
read ADMINPASS


[ -f /var/se3/Progs/install/installdll/confse3.ini-root ] || cp /var/se3/Progs/install/installdll/confse3.ini /var/se3/Progs/install/installdll/confse3.ini-root

sed -i  "s/compte_ldap_domain=root/compte_ldap_domain=adminse3/" /var/se3/Progs/install/installdll/confse3.ini
sed -i "/password_ldap_domain=/d" /var/se3/Progs/install/installdll/confse3.ini
echo  "password_ldap_domain=$XPPASS" >> /var/se3/Progs/install/installdll/confse3.ini  

echo "attention, la mise au domaine se fait maintenant avac le compte adminse3, mot de passe $XPPASS
le compte root samba est maintenant desactive"

net -U admin%$ADMINPASS  rpc rights grant adminse3 SeMachineAccountPrivilege SePrintOperatorPrivilege
net -U admin%$ADMINPASS  rpc rights grant admin SeMachineAccountPrivilege SePrintOperatorPrivilege

echo "ATTENTION A VERIFIER
la mise en pace des privileges d administration peut causer des problemes avec les pilotes d imprimantes
si vous constatez que les pilotes ne fonctionnent plus, c est qu il faut les uploader a nouveau sur le 
serveur ( voir la procedure de la doc)"

# Migration vers nouveau code de gestion de restrictions
/usr/share/se3/sbin/registreMigrate2GPO.sh

echo "Mise a jour 112:
- Ajout des groupes Domain Users et Domain Guests
- Modification du sambaPrimaryGroupSID
- Ajout des groupes et utilisateurs manquants pour ldapsam:trusted
- passage à ldapsam:trusted
- ajout des privileges
- Migration vers nouveau code de gestion de restrictions
- correction des scripts imprimantes pour ne donner les droits qu à adminse3" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0

