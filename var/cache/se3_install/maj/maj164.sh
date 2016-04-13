#!/bin/bash

## $Id:$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj164"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
LADATE=$(date +%d-%m-%Y)
PASS_SQL="$(grep -vE '^[[:space:]]*#' /root/.my.cnf | grep password /root/.my.cnf | cut -d "=" -f2)"
exec 2>&1
echo "Mise a jour 164 :
- Modification des sources SE3 pour utilisation de la branche stable
"

echo "Mise a jour 164 :
- Modification des sources SE3 pour utilisation de la branche stable
">> $HISTORIQUE_MAJ
 
cat >/etc/apt/sources.list.d/se3.list <<END
# sources pour se3
deb http://wawadeb.crdp.ac-caen.fr/debian wheezy se3

#### Sources testing desactivee en prod ####
#deb http://wawadeb.crdp.ac-caen.fr/debian wheezy se3testing

#### Sources backports smb41  ####
deb http://wawadeb.crdp.ac-caen.fr/debian wheezybackports smb41
END

if  [ -e /var/www/se3/dhcp ];then
	echo "modification du paramètre de la bdd dhcp_ip_min pour correction accent"
	mysql -u root -p$PASS_SQL -D se3db -e "UPDATE params SET descr='Début de la plage de reservation (par defaut network + 51)' WHERE name='dhcp_ip_min';"
	echo "modification du paramètre de la bdd dhcp_ip_min pour correction accent" >> $HISTORIQUE_MAJ
fi

exit 0
