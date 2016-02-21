#!/bin/bash

## $Id:$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj163"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)

SQL_DUMP="/var/cache/se3_install/se3db-utf8.sql"

echo "Mise a jour 163 :
- ajout tache cron effaceemnt des profiles
- Migration de la bdd en utf8
"

echo "Mise a jour 163 :
- ajout tache cron effaceemnt des profiles
- Migration de la bdd en utf8
">> $HISTORIQUE_MAJ
 
# Fait directement dans le fichier /etc/cron.d/se3 qui descend a chaque maj
# echo "# effacement des profils a effacer toutes les 2 minutes
# */2 * * * * root /usr/share/se3/sbin/clean_profiles.sh" >> /etc/cron.d/se3

mysqldump -c -e --default-character-set=utf8 --single-transaction --skip-set-charset --add-drop-database -B se3db > "$SQL_DUMP" 

sed -i 's/DEFAULT CHARACTER SET latin1/DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci/' "$SQL_DUMP" 
sed -i 's/DEFAULT CHARSET=latin1/DEFAULT CHARSET=utf8/' "$SQL_DUMP" 

mysql se3db < "$SQL_DUMP" 

exit 0
