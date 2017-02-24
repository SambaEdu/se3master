#!/bin/bash

## $Id:$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj166"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)
PASS_SQL="$(grep -vE '^[[:space:]]*#' /root/.my.cnf | grep password /root/.my.cnf | cut -d "=" -f2)"
exec 2>&1
echo "Mise a jour 166 :
- Correction de l'annuaire compatibilité samba 4.4
"

echo "Mise a jour 166 :
- Correction de l'annuaire compatibilité samba 4.4
">> $HISTORIQUE_MAJ
 
/usr/share/se3/sbin/corrige_ldap_smb44.sh
 
 
exit 0
