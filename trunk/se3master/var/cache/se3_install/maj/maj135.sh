#!/bin/bash

## $Id: maj134.sh 5921 2010-11-06 14:14:02Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj135"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"
[ -e /usr/share/se3/scripts/update_droits_xml.sh ] && /usr/share/se3/scripts/update_droits_xml.sh
#date
LADATE=$(date +%d-%m-%Y)

echo "Mise a jour 135:
- nettoyage des homes ce soir a 20h00" >> $HISTORIQUE_MAJ

echo "Programmation du nettoyage des homes ce soir a 20h00"
echo "les fichiers obsoletes seront deplaces dans /home/admin/Trash_users"
/usr/share/se3/scripts/clean_homes.sh -sm 2>&1

config_ff="/var/se3/unattended/install/packages/firefox/firefox-profile.js"
if [ -e "$config_ff" ]; then
	if [ -n "$(grep "network.proxy.type" $config_ff | head -n1 | grep "1")" ]; then
		proxy=$(grep "network.proxy.http" $config_ff | head -n1 | grep -v port | cut -d"'" -f4)
		port=$(grep "network.proxy.http_port" $config_ff | head -n1 | cut -d" " -f2 | cut -d")" -f1)

		if [ -n "$proxy" -a -n "$port" ]; then 
			SETMYSQL proxy_url "$proxy:$port" "url du proxy pour le navigateur" 1
			SETMYSQL proxy_type "1" "type du proxy (param IE / aucun / manuel / url auto" 1
			/usr/share/se3/scripts/deploy_mozilla_ff_final.sh shedule
		fi
	fi

	if [ -n "$(grep "network.proxy.type" $config_ff | head -n1 | grep "2")" ]; then
		proxy_url=$(grep "network.proxy.autoconfig_url" $config_ff | head -n1 | cut -d"'" -f4)
		if [ -n "$proxy_url" ]; then 
			SETMYSQL proxy_url "$proxy_url" "url du proxy pour le navigateur" 1
			SETMYSQL proxy_type "2" "type du proxy (param IE / aucun / manuel / url auto" 1
			/usr/share/se3/scripts/deploy_mozilla_ff_final.sh shedule

		fi
	fi

	
	
fi
SETMYSQL firefox_use_ie "default" "Firefox utilise ou non les param proxy de IE" 1

exit 0		
