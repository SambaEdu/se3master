#!/bin/bash

## $Id:$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj162"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)


echo "Mise a jour 163 :
- ajout tache cron effaceemnt des profiles
">> $HISTORIQUE_MAJ
 
echo "# effacement des profils a effacer toutes les 2 minutes
*/2 * * * * root /usr/share/se3/sbin/clean_profiles.sh" >> /etc/cron.d/se3


exit 0
