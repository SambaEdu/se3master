#!/bin/bash

## $Id: maj112.sh 4622 2009-10-24 21:41:58Z keyser $ ##

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

mysql -f se3db < /var/cache/se3_install/se3db.sql 2>/dev/null

MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail à l'admin
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 112" $MAIL_ADMIN
fi
}


# Modif table connexions
echo "Correction table connexions"
echo "ALTER TABLE connexions CHANGE id id BIGINT( 20 ) NOT NULL AUTO_INCREMENT" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass

# Ajout pour logrotate.d/samba
echo "Nettoyage anciens logs samba"
mkdir -p /var/log/samba/old
rm -f /var/log/samba/*.old

# Correction de droits
echo "Remise en place des droits - execution de permse3"
/usr/share/se3/sbin/permse3

echo "Mise a jour 112:
- Correctif logs samba
- Correctif sur printers_parcs.pl 
- Correction bug mineur sur correctSID
- Passage de l'index de la table connexions en bigint(20) et Correctif auto increment" >> $HISTORIQUE_MAJ
  
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0
