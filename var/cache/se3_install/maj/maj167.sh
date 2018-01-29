#!/bin/bash

## $Id:$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj167"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)
PASS_SQL="$(grep -vE '^[[:space:]]*#' /root/.my.cnf | grep password /root/.my.cnf | cut -d "=" -f2)"
exec 2>&1
echo "Mise a jour 167 :
- Correction clé excludedir
- Correction dimensions table params
- Ajout fonctionalites interface pour gestion utilisateurs
- Ajout import csv ENT27
"

echo "Mise a jour 167 :
- Correction de l'annuaire compatibilité samba 4.4
- Correction clé excludedir
- Correction dimensions table params
- Ajout fonctionalites interface pour gestion utilisateurs
- Ajout import csv ENT27
">> $HISTORIQUE_MAJ
 mysql -u root -p$PASS_SQL -D se3db -e "update corresp set valeur = 'Local Settings;Temporary Internet Files;Historique;Temp;Application Data;AppData\\\\Local;AppData\\\\LocalLow;$Recycle.Bin;OneDrive;Work Folders' where Intitule = 'excludedir' ;"
exit 0
