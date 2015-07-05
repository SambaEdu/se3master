#!/bin/bash

#
## $Id$ ##
#
##### script de modif de /etc/profile afin que la machine passe par un proxy #####
##### positionne egalement le proxy des clients FF 

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Modifie /etc/profile pour ajouter la conf d'un proxy"
	echo "Relance la conf dhcp pour creation wpad et se3.pac si besoin"
	echo "Modifie les valeurs msql proxy_url et proxy_type"
	echo "Modifie les fichiers prefs.js Firefox"
	echo "Sans option le proxy est supprimé"
	echo "Usage : modifProxy.sh [adresse_ip:port]"
	exit
fi	

. /usr/share/se3/includes/config.inc.sh -cms
. /usr/share/se3/includes/functions.inc.sh

# Si on a deja un proxy
proxy=`cat /etc/profile | grep http_proxy=` 
if [ "$proxy" != "" ]
then 
	perl -pi -e 's/http_proxy=.*\n//' /etc/profile
	perl -pi -e 's/https_proxy=.*\n//' /etc/profile
	perl -pi -e 's/ftp_proxy=.*\n//' /etc/profile
	perl -pi -e 's/.*http_proxy.*\n//' /etc/profile
	perl -pi -e 's/^http_proxy = .*\n//' /etc/wgetrc
	perl -pi -e 's/^https_proxy = .*\n//' /etc/wgetrc
	
	
fi	
if [ "$1" != "" ]
then
	echo "http_proxy=\"http://$1\"" >> /etc/profile
	echo "https_proxy=\"http://$1\"" >> /etc/profile
	echo "ftp_proxy=\"http://$1\"" >> /etc/profile
	echo "export http_proxy https_proxy ftp_proxy" >> /etc/profile
	echo "http_proxy = http://$1" >> /etc/wgetrc
	echo "https_proxy = http://$1" >> /etc/wgetrc
	
fi
PROXY=$(echo $1 | cut -d: -f1)
PORT=$(echo $1 | cut -d: -f2)

#modif proxy firefox
rm -f /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js 
cp /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js.in /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js 
PREF_JS_FF="/etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js"


if [ -n "$PROXY" ]; then

	if [ "$slisip" = "$PROXY"  ];	then
		SETMYSQL proxy_url "http://$slisip/cgi-bin/slis.pac" "url du proxy pour le navigateur" 1
		SETMYSQL proxy_type "2" "type du proxy (param IE / aucun / manuel / url auto" 1
	else
		if [ "$dhcp" = "1" ]; then 
			/usr/share/se3/scripts/makedhcpdconf 
			SETMYSQL proxy_url "http://$se3ip/se3.pac" "url du proxy pour le navigateur" 1
			SETMYSQL proxy_type "2" "type du proxy (param IE / aucun / manuel / url auto" 1
		else
			SETMYSQL proxy_url "$PROXY:$PORT" "url du proxy pour le navigateur" 1
			SETMYSQL proxy_type "1" "type du proxy (param IE / aucun / manuel / url auto" 1
		fi	
	fi

else

	rm -f /var/www/se3.pac
	rm -f /var/www/wpad.dat
	
	if [ "$slisip" != "" ];	then
		SETMYSQL proxy_url "http://$slisip/cgi-bin/slis.pac" "url du proxy pour le navigateur" 1
		SETMYSQL proxy_type "2" "type du proxy (param IE / aucun / manuel / url auto" 1
	else
		SETMYSQL proxy_url "" "url du proxy pour le navigateur" 1
		SETMYSQL proxy_type "0" "type du proxy (param IE / aucun / manuel / url auto" 1
	fi

fi

### Ajouter lancement profil FF
/usr/share/se3/scripts/deploy_mozilla_ff_final.sh shedule

SETMYSQL firefox_use_ie "default" "Firefox utilise ou non les param proxy de IE" 1
exit 0

