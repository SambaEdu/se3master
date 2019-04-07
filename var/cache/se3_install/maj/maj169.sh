#!/bin/bash

## $Id:$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj169"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)
exec 2>&1
echo "Mise a jour 169 :
- Modification des sources debian wheezy"

echo "Mise a jour 169 :
- Modification des sources debian wheezy
">> $HISTORIQUE_MAJ

# Nouvelles sources wheezy
cat >/etc/apt/sources.list <<END
# Sources standard:
deb http://archive.debian.org/debian/ wheezy main non-free contrib

END

exit 0
