#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj142"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
# [ -e /usr/share/se3/scripts/update_droits_xml.sh ] && /usr/share/se3/scripts/update_droits_xml.sh
#date
LADATE=$(date +%d-%m-%Y)

echo "Mise a jour 142 :
- Passage a bash des derniers scripts en bin/sh
- Contournement du bug libnss sur squeeze
- Prise en compte pb onduleur avec nut
- Suppression bash sftp pour LCS" >> $HISTORIQUE_MAJ
 
/usr/share/se3/scripts/mkSlapdConf.sh

echo "MODE=standalone" > /etc/nut/nut.conf


echo "Mise a jour 142 :
- Passage a bash des derniers scripts en bin/sh
- Contournement du bug libnss sur squeeze
- Prise en compte pb onduleur avec nut
- Suppression bash sftp pour LCS"


exit 0		
