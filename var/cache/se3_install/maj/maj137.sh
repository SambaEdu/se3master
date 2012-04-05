#!/bin/bash

## $Id:$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj137"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
# [ -e /usr/share/se3/scripts/update_droits_xml.sh ] && /usr/share/se3/scripts/update_droits_xml.sh
#date
LADATE=$(date +%d-%m-%Y)

echo "Mise a jour 137:
- Effacement des enregistrements foireux dans cn=machines" >> $HISTORIQUE_MAJ

echo "- Effacement des enregistrements foireux dans cn=machines"
/usr/share/se3/sbin/clean_ip_machine.sh
exit 0		
