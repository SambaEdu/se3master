#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj120
mkdir -p /root/maj/1.50/
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)


mysql -u $dbuser -p$dbpass -f se3db < /var/cache/se3_install/se3db.sql 2>/dev/null



MAIL_REPORT()
{
if [ -e /etc/ssmtp/ssmtp.conf ]; then
    MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
fi
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
grep -q "nss_initgroups_ignoreusers" /etc/libnss-ldap.conf || echo "nss_initgroups_ignoreusers root,openldap,plugdev,disk,kmem,tape,audio,daemon,lp,rdma,fuse,video,dialout,floppy,cdrom,tty" >> /etc/libnss-ldap.conf

# test id backuppc et modif si necessaire
id_backuppc="$(id backuppc -u)"
id_wwwse3="$(id www-se3 -u)"

if [ "$id_backuppc" == "$id_wwwse3" ]; then
usermod -u 104 backuppc
CHANGEMYSQL bck_uidnumber "104" 
fi


echo  "Ajout des groupes Samba3 obligatoires dans LDAP"
DOMAINSID=`net getlocalsid | cut -d: -f2 | sed -e "s/ //g"`
sed -e "s/#BASEDN#/$BASEDN/g;s/#DOMAINSID#/$DOMAINSID/g;s/#GROUPS#/$GROUPSR/g;s/#PEOPLE#/$PEOPLERDN/g" ldif/Samba.ldif > /root/Samba_maj120.ldif
ldapadd -x -c -D "$ADMINRDN,$BASEDN" -w $ADMINPW -f /root/Samba_maj120.ldif
echo ""

# echo "maj supervision rouen" 
./depmaj/install_supervision_rouen.sh


# mappage des groupes particuliers
echo "Mapping des groupes particuliers"
echo ""
# groupe lcs-users mappé vers "utilisateurs du domaine -513"
net groupmap list | grep -q "lcs-users" || net groupmap add ntgroup="Utilisateurs du domaine" rid="513" unixgroup="lcs-users" type="domain"

# groupe machines mappé vers -515
net groupmap list | grep -q "machines" || net groupmap add ntgroup="machines" rid="515" unixgroup="machines" type="domain"

# groupe admins mappé vers -512
net groupmap list | grep -q "admins" || net groupmap  add ntgroup="Admins" rid="512" unixgroup="admins" type="domain"

# groupe profs / eleves /root rid auto
net groupmap list | grep -q "Profs" || net groupmap add ntgroup="Profs" unixgroup="Profs" type="domain" comment="Profs du domaine"
net groupmap list | grep -q "Eleves" || net groupmap add ntgroup="Eleves" unixgroup="Eleves" type="domain" comment="Eleves du domaine"
echo ""

# mise a jour des parametres caches pour le domaine si besoin ( remplacement confse3.ini )
echo "Remplacement du fichier confse3.ini par des parametres dans la base se3db"

workgroup=$(grep "workgroup =" /etc/samba/smb.conf | grep -v "^#" | cut -d"=" -f2| sed "s/ //g")
CHANGEMYSQL se3_domain "$workgroup" 

netbiosname=$(grep "netbios name =" /etc/samba/smb.conf |grep -v "^#" | cut -d"=" -f2| sed "s/ //g")
CHANGEMYSQL netbios_name "$netbiosname" 

se3ip=$(nmblookup $netbiosname | grep "$netbiosname<00>" | cut -d " " -f1)
CHANGEMYSQL se3ip "$se3ip" 

CHANGEMYSQL bck_user "backuppc" 

. /usr/share/se3/includes/config.inc.sh -clpbmsdf

echo "mise en place des privileges samba"
# 
rm -rf /var/se3/Progs/install/installdll/

ldapsearch -xLLL cn=root -b $BASEDN cn | grep -q "cn=root" || \
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

# mappage des groupes pour samba

ldapsearch -xLLL -h $LDAPIP -b $GROUPSRDN,$BASEDN "(&(objectClass=posixGroup)(!(objectClass=sambaGroupMapping)))" cn | grep "^cn:"  | cut -c 5- | while read cn; do
	echo "mappage de $cn" 
    /usr/share/se3/scripts/group_mapping.sh $cn
done

# Creation compte adminse3 dans annuaire si besoin et application provileges smbpour admin et adminse3 
/usr/share/se3/sbin/create_adminse3.sh

echo ""
echo "ATTENTION A VERIFIER
la mise en pace des privileges d administration peut causer des problemes 
avec les pilotes d imprimantes si vous constatez que les pilotes ne fonctionnent plus,
c est qu il faut les uploader a nouveau sur le serveur (voir la procedure de la doc)
"

# bye bye se3printers
rm -f /var/se3/Progs/ro/printers/se3printers.bat
# sed -i "/se3printers.bat/d" /home/templates/base/logon.bat
for logon in /home/templates/*/logon*.bat
do
	sed -i "/se3printers.bat/d" $logon
done


. /usr/share/se3/includes/config.inc.sh -clpbmsdf

# deplacement corbeille reseau si besoin ?
for user in /home/*
do	
	if [ -d "$user"/profil/Bureau/Corbeille_Reseau ]; then 
	    mv  "$user"/profil/Bureau/Corbeille_Reseau "$user"/Corbeille_Reseau
	    
	fi
	
	if  [ -e "$user"/Bureau ]; then 
	find "$user"/Bureau/ -user root -iname "*.lnk" -exec rm {} \;
	fi
done
#/usr/share/se3/sbin/update-smbconf.sh fait par instance_se3.sh

#Change le SambaPrimaryGroupe
echo "#!/bin/bash
echo \"listage des utilisateurs\"
ldapsearch -x -b $PEOPLERDN,$BASEDN '(objectclass=*)' uid | grep -v People | grep -v \# | grep uid: | cut -d\" \" -f2 | grep -v webmaster.etab | while read ID
do
echo \"dn: uid=\$ID,$PEOPLERDN,$BASEDN
changetype: modify
replace: sambaPrimaryGroupSID
sambaPrimaryGroupSID: $DOMAINSID-513
\" >> /root/maj/1.50/modif_SambaPrimaryGroupe.ldif
done
echo \"modification des entrees\" 
ldapmodify -x -D "$ADMINRDN,$BASEDN" -w "$ADMINPW" -f /root/maj/1.50/modif_SambaPrimaryGroupe.ldif >/root/maj/1.50/rapport_modif_annuaire.txt
" >/root/maj/1.50/modif_SambaPrimaryGroupe.sh
chmod 700 /root/maj/1.50/modif_SambaPrimaryGroupe.sh
echo "Modification du SambaPrimaryGroupe en cours....Ce peut être long......."
cd /root/maj/1.50/
./modif_SambaPrimaryGroupe.sh && echo "Termine avec succes"
cd - >/dev/null

mv  /root/Samba_maj120.ldif /root/maj/1.50/

echo "Mise a jour 120:
- Ajout nouvelle architecture de connexion
- Ajout adminse3 dans l'annuaire par defaut
- Ajout corbeille reseau
- Ajout integration vista
- Ajout des groupes Domain Users et Domain Guests
- Modification du sambaPrimaryGroupSID
- ajout des privileges mise au domaine adminse3 et admin, desativation root samba
- Migration vers nouveau code de gestion de restrictions
- correction des scripts imprimantes pour ne donner les droits que pour adminse3" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "
Un mail recapitulatif de la mise a jour sera envoye
"
/etc/init.d/slapd restart
exit 0
