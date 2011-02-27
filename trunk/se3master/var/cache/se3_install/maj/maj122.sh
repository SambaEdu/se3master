#!/bin/bash

## $Id: maj122.sh 5499 2010-05-06 00:05:44Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj122"
mkdir -p /root/maj/1.50/
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)


mysql -u $dbuser -p$dbpass -f se3db < /var/cache/se3_install/se3db.sql 2>/dev/null



echo "Mise a jour 122:
- Nouvelle entree dans mysql : hide_logon" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise a jour sera envoye"
#/etc/init.d/slapd restart
exit 0
