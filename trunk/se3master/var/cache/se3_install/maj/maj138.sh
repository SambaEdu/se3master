#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj138"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
# [ -e /usr/share/se3/scripts/update_droits_xml.sh ] && /usr/share/se3/scripts/update_droits_xml.sh
#date
LADATE=$(date +%d-%m-%Y)

echo "Mise a jour 138:
- Correctif pour mysql sup 5.1.12
- Suppression de l'utilisation de nscd (doublons uidnumbers) " >> $HISTORIQUE_MAJ
 
echo "- Correctif pour mysql sup 5.1.12
- Suppression de l'utilisation de nscd (doublons uidnumbers)"

# On assure la comptibilite mysql superieur a 5.1.12
sed -i 's/^skip-bdb/#skip-bdb/g'  /etc/mysql/my.cnf

# nscd sucks !
if [ -e /etc/init.d/nscd  ]; then
	insserv -r nscd
	/etc/init.d/nscdstop
fi

exit 0		
