#!/bin/bash

## $Id: maj144.sh 7589 2013-01-06 01:11:10Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj144"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)

echo "Mise a jour 144 :
- Fonctionnement ldap avec user openldap
- les droits du groupe profs permettent d'etendre le droit sovajon_is_admin à tous les eleves
- Correction page de demarrage FF lors de la creation homedir
- Nouvelles politiques de de gestion des passes possible 
- Correction bug fixer un quota sur un utilisateur
- Prise en compte proxy de /etc/profile si existant lors du script update system
- possibilité de corriger les pbs lies a wpkg dans info systeme
- Amelioration script miroir">> $HISTORIQUE_MAJ
 
/usr/share/se3/scripts/mkSlapdConf.sh


echo "Mise a jour 144 :
- Fonctionnement ldap avec user openldap
- les droits du groupe profs permettent d'etendre le droit sovajon_is_admin à tous les eleves
- Correction page de demarrage FF lors de la creation homedir
- Nouvelles politiques de de gestion des passes possible 
- Correction bug fixer un quota sur un utilisateur
- Prise en compte proxy de /etc/profile si existant lors du script update system
- possibilité de corriger les pbs lies a wpkg dans info systeme
- Amelioration script miroir"

exit 0		
