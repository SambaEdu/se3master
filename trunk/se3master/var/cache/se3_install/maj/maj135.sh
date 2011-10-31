#!/bin/bash

## $Id: maj134.sh 5921 2010-11-06 14:14:02Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj135"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
[ -e /usr/share/se3/scripts/update_droits_xml.sh ] && /usr/share/se3/scripts/update_droits_xml.sh
#date
LADATE=$(date +%d-%m-%Y)

echo "Mise a jour 135:
- nettoyage des homes ce soir a 20h00" >> $HISTORIQUE_MAJ

echo "Programmation du nettoyage des homes ce soir a 20h00"
echo "les fichiers obsoletes seront deplaces dans /home/admin/Trash_users"
/usr/share/se3/scripts/clean_homes.sh -sm
exit 0		
