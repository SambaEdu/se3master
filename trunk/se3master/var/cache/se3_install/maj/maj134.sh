#!/bin/bash

## $Id: maj134.sh 5921 2010-11-06 14:14:02Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj134"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

echo "Correctif si besoin pour backuppc"

#date
LADATE=$(date +%d-%m-%Y)
if [ -e  /usr/share/backuppc/lib/BackupPC/Lib.pm ]; then
	tar -zxf /var/cache/se3_install/maj/bpcfix_maj134.tgz -C /
	chmod 644  /usr/share/backuppc/lib/BackupPC/Lib.pm
	chown www-se3 /usr/share/backuppc/lib/BackupPC/Lib.pm
fi

echo "Mise a jour 134:
- correctif si besoin sur le Lib.pm de backuppc" >> $HISTORIQUE_MAJ

exit 0		
