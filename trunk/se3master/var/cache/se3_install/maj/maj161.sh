#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj161"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)


echo "Mise a jour 161 :
- Mise en place nouvelle structure sudoers"

if [ -e /etc/sudoers.dpkg-dist ]; then
  [ ! -e /etc/sudoers.d/sudoers-se3 ] && mv /etc/sudoers /etc/sudoers.d/sudoers-se3
  mv /etc/sudoers.dpkg-dist /etc/sudoers && echo "/etc/sudoers.dpkg-dist restauré"
fi
 
echo "Mise a jour 161 :
- Mise en place nouvelle structure sudoers">> $HISTORIQUE_MAJ
 

exit 0		
