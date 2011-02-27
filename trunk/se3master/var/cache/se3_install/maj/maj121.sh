#!/bin/bash

## $Id: maj121.sh 5427 2010-04-21 00:16:35Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj121"
mkdir -p /root/maj/1.50/
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)



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

echo "mappage des groupes pour samba en cours.....Patientez"
ldapsearch -xLLL -h $LDAPIP -b $GROUPSRDN,$BASEDN "(&(objectClass=posixGroup)(!(objectClass=sambaGroupMapping)))" cn | grep "^cn:"  | cut -c 5- | while read cn; do
    /usr/share/se3/scripts/group_mapping.sh $cn >> $REPORT_FILE
done
echo "Mappage Terminé"
#supression deploiement imprimantes plus utile desormais
if [ -e /var/www/se3/wpkg/bin/associer.sh ]; then
	/var/www/se3/wpkg/bin/associer.sh Dissocier DeploiementImprimantes _TousLesPostes admin
fi

echo "Mise a jour 121:
- mappage des groupes pour samba" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise a jour sera envoye"
#/etc/init.d/slapd restart
exit 0
