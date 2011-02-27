#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj131"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)

# insert donnees manquantes si besoin
mysql -u $dbuser -p$dbpass -f se3db < /var/cache/se3_install/se3db.sql 2>/dev/null

[ -z "$ecard" ] &&  ecard="$(grep iface /etc/network/interfaces | grep static | sort | head -n1 | awk '{print $2}')"

[ -z "$se3mask" ] && se3mask=$(grep netmask  /etc/network/interfaces | head -n1 | sed -e "s/netmask//g" | tr "\t" " " | sed -e "s/ //g")
CHANGEMYSQL se3mask "$se3mask" 

CHANGEMYSQL ecard "$ecard" 

echo "Deux nouvelles valeurs viennent d'etre inserees dans la table params de se3db
masque de sous reseau : $se3mask
nom de la carte reseau  : $ecard

Ces valeurs ont ete detectees automatiquement. En cas d'erreur, vous devrez les modifier en mode sans echec
http://$se3ip:909/setup/"
sleep 4


echo "Mise a jour 131:
- nouvelles entrees dans mysql table params : se3mask et ecard" >> $HISTORIQUE_MAJ

exit 0
