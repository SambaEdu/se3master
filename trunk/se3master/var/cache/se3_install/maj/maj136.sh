#!/bin/bash

## $Id: maj136.sh 6817 2012-01-22 18:52:33Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj136"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
# [ -e /usr/share/se3/scripts/update_droits_xml.sh ] && /usr/share/se3/scripts/update_droits_xml.sh
#date
LADATE=$(date +%d-%m-%Y)

echo "Mise a jour 136:
- Correction droits sur Progs/ro" >> $HISTORIQUE_MAJ

echo "- Correction droits sur Progs/ro : admin:lcs-users / 755"
find /var/se3/Progs/ro/* -maxdepth 0 -type d -print -exec chown admin:lcs-users {} \; -exec chmod 755 {} \; 

exit 0		
