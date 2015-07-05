#!/bin/bash
#

## $Id$ ##

#
# script permettant de ventiler le proxy pour firefox selon le choix de l'interface web
# franck molle 10/2011


if [ "$1" = "-h" -o "$1" = "--help" ]
then
	echo "Script permettant de ventiler les profils firefox en tenant compte de la presence d'un slis ou non"
	echo "Si l'ip du slis est déclarée dans l'interface, le proxy sera déclaré dans le prefs.js des clients"
	echo "Usage : sauve_book en argument permet de sauvegarder les bookmarks d'un profil déjà existant"
	echo "Sans argument le profil est remplacé mais une sauvegarde de l'ancien est effectuée"
exit
fi
chemin_html="/var/www/se3/tmp"
LADATE=$(date +%D_%Hh%M | sed -e "s!/!_!g")
WWWPATH="/var/www"


if [ "$1" = "shedule" ]; then 
	at now +1 minute -f $0  
	exit 0
fi

mkdir -p /var/se3/save

/usr/share/se3/includes/config.inc.sh -cf
. /etc/se3/config_c.cache.sh
. /etc/se3/config_m.cache.sh
#/usr/share/se3/includes/config.inc.sh -cm


if [ "$1" = "refparams" ]; then 
	exit 0
fi

#Seuls les homes deja existants seront complétés
CHEMIN_FF_SOURCE="/etc/skel/user/profil/appdata/Mozilla"

#======================================================
# Nombre de dossiers à traiter:
nbdossiers=$(ls /home | grep -v netlogon | grep -v templates  | grep -v profiles | wc -l)
nbdossiers=$(($nbdossiers-2))
compteur=1

mkdir -p $chemin_html
chown www-se3 $chemin_html

echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"refresh\" content=\"2\">
<title>Traitement des profils</title>
</head>
<body>
<h1 align=\"center\">Traitement des profils</h1>
<p align=\"center\">Le traitement va démarrer...<br></p>
</body>
</html>" > $chemin_html/recopie_profils_firefox.html
chmod 755 $chemin_html/recopie_profils_firefox.html
chown www-se3 $chemin_html/recopie_profils_firefox.html
#======================================================




rm -f /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js 
cp /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js.in /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js 
PREF_JS_BASE="/etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js"


if [ "$firefox_use_ie" != "1" ]; then
	case $proxy_type in	
		0)  sed -i '/proxyurl/d'  $PREF_JS_BASE
			sed -i '/proxytype/d'  $PREF_JS_BASE
			echo "user_pref(\"network.proxy.type\", $proxy_type);" >> $PREF_JS_BASE
			;;

		1) 	sed -i '/proxyurl/d'  $PREF_JS_BASE
			sed -i '/proxytype/d'  $PREF_JS_BASE
			PROXY=$(echo $proxy_url | cut -d: -f1)
			PORT=$(echo $proxy_url | cut -d: -f2)
			echo "user_pref(\"network.proxy.http\", \"$PROXY\");" >> $PREF_JS_BASE
			echo "user_pref(\"network.proxy.http_port\", $PORT);" >> $PREF_JS_BASE
			echo "user_pref(\"network.proxy.type\", 1);"  >> $PREF_JS_BASE
			;;

		2) 	sed -i '/proxyurl/d'  $PREF_JS_BASE
			sed -i '/proxytype/d'  $PREF_JS_BASE
			echo "user_pref(\"network.proxy.autoconfig_url\", \"$proxy_url\");" >> $PREF_JS_BASE
			echo "user_pref(\"network.proxy.type\", $proxy_type);" >> $PREF_JS_BASE
			;;
	esac
else
	sed -i '/proxyurl/d'  $PREF_JS_BASE
			sed -i '/proxytype/d'  $PREF_JS_BASE
fi

# copie prefs.js to clients-linux
if [ -e /home/netlogon/clients-linux ]; then
	for distrib in $(ls /home/netlogon/clients-linux/distribs)
	do
 # /home/netlogon/clients-linux/distribs/wheezy/skel/.mozilla/firefox/default/prefs.js

		if  [ -e /home/netlogon/clients-linux/distribs/$distrib/skel/.mozilla/firefox/default/prefs.js ]; then
			cp $PREF_JS_BASE /home/netlogon/clients-linux/distribs/$distrib/skel/.mozilla/firefox/default/prefs.js
			echo "modif proxy par interface web le $LADATE" > /home/netlogon/clients-linux/distribs/$distrib/skel/.VERSION
		fi
	done
fi


for user in $(ls /home | grep -v netlogon | grep -v templates | grep -v profiles | grep -v _netlogon | grep -v _templates)
do
	PREF_JS_USER="/home/$user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js"
	HOME_PAGE=$(grep '\"browser.startup.homepage\",' "$PREF_JS_USER")
	
	echo "Traitement de $user"
	#===================================================
	echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<meta http-equiv=\"refresh\" content=\"2\">
<title>Traitement des profils</title>
</head>
<body>
<h1 align=\"center\">Traitement des profils</h1>
<p align=\"center\">Traitement de $user...<br>($compteur/$nbdossiers)</p>
</body>
</html>" > $chemin_html/recopie_profils_firefox.html
	#===================================================
	echo "Déploiement du profil mozilla de $user"
	rm -f "$PREF_JS_USER"
	cp "$PREF_JS_BASE" "$PREF_JS_USER"
	if [ ! -z "$HOME_PAGE" ]; then
		echo "$HOME_PAGE" >> $PREF_JS_USER
		echo "Restauration homepage : $HOME_PAGE"
	fi
	chown $user:admins "$PREF_JS_USER"
	#============================================
	compteur=$(($compteur+1))
	#============================================
	echo -e "$COLTXT"
done
#============================================
echo "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.01 Transitional//EN\" \"http://www.w3.org/TR/html4/loose.dtd\">
<html>
<head>
<title>Traitement des profils</title>
</head>
<body>
<h1 align=\"center\">Traitement des profils</h1>
<p align=\"center\">Traitement terminé !</p>
</body>
</html>" > $chemin_html/recopie_profils_firefox.html
#============================================
exit 0
