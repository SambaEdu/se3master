#!/bin/bash
#
## $Id$ ##
#
# script permettant de ventiler le profil de Firefox
# sur les comptes deja crées. Si un profil Firefox a été crée par un utilisateur,
# celui ci ne sera pas écrasé mais le script fera en sorte que soit utilisé celui nouvellement deployé .
# franck molle 05/2005  version 0.2
# possibilité de sauvegarder le fichier bookmarks en ajoutant en arg sauve_book

# Modifs Stephane Boireau: 03/03/2006
# correction keyser bug sur sed 09-2006


if [ "$1" == "-h" -o "$1" == "--help" ]
then
	echo "Script permettant de ventiler les profils firefox en tenant compte de la presence d'un slis ou non"
	echo "Si l'ip du slis est déclarée dans l'interface, le proxy sera déclaré dans le pref.js des clients"
	echo "Usage : sauve_book en argument permet de sauvegarder les bookmarks d'un profil déja existant"
	echo "Sans argument le profil est remplacé mais une sauvegarde de l'ancien est effectuée"
exit
fi
chemin_html="/var/www/se3/tmp"
LADATE=$(date +%D_%Hh%M | sed -e "s!/!_!g")
WWWPATH="/var/www"

mkdir -p /var/se3/save

. /usr/share/se3/includes/config.inc.sh -cm


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

PROXY=$(grep "http_proxy=" /etc/profile | cut -d"=" -f2 | sed -e "s#http://##g;s/\"//g" | cut -d: -f1)

PORT=$(grep "http_proxy=" /etc/profile | cut -d"=" -f2 | sed -e "s#http://##g;s/\"//g" | cut -d: -f2)

#modif proxy firefox
rm -f /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js 
cp /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js.in /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js 
PREF_JS_FF="/etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js"
  


if [ -n "$PROXY" ]; then

	if [ "$slisip" == "$PROXY"  ];	then
		sed -i 's/%proxytype%/2/g' $PREF_JS_FF
		sed -i "s/%proxyurl%/http:\/\/$slisip\/cgi-bin\/slis.pac/g" $PREF_JS_FF
	else
		if [ "$dhcp" == "1" ]; then 
		  /usr/share/se3/scripts/makedhcpdconf 
		  sed -i 's/%proxytype%/2/g' $PREF_JS_FF
		  sed -i "s/%proxyurl%/http:\/\/$se3ip\/se3.pac/g" $PREF_JS_FF
		else
		  sed -i '/%proxyurl%/d' $PREF_JS_FF
		  sed -i '/%proxytype%/d' $PREF_JS_FF
		  echo "user_pref(\"network.proxy.http\", \"$PROXY\");" >> $PREF_JS_FF
		  echo "user_pref(\"network.proxy.http_port\", $PORT);" >> $PREF_JS_FF
		  echo "user_pref(\"network.proxy.type\", 1);"  >> $PREF_JS_FF
		
		fi	
	fi


	

else
	rm -f /var/www/se3.pac
	rm -f /var/www/wpad.dat
	
	if [ "$slisip" != "" ];	then
		sed -i 's/%proxytype%/2/g' $PREF_JS_FF
		sed -i "s/%proxyurl%/http:\/\/$slisip\/cgi-bin\/slis.pac/g" $PREF_JS_FF
	else
		sed -i 's/%proxytype%/0/g' $PREF_JS_FF
		sed -i '/%proxyurl%/d' $PREF_JS_FF
	fi

fi



for user in $(ls /home | grep -v netlogon | grep -v templates | grep -v profiles | grep -v _netlogon | grep -v _templates)
do


	PREF_JS="/home/$user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js"
	CHEMIN_CIBLE="/home/$user/profil/appdata/Mozilla/Firefox/Profiles/default"
	CHEMIN_FF_CIBLE="/home/$user/profil/appdata/Mozilla"
	FICHIER_PROFILES="${CHEMIN_FF_CIBLE}/Firefox/profiles.ini"
	BOOK=""


	echo -e "$COLINFO"
	echo -e "Traitement de $user $COLTXT"

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

	if [ -e $FICHIER_PROFILES ]; then
	# 	echo "grep 'Path=Profiles/default' "$FICHIER_PROFILES""
		TYPE_PROFILE=$(grep 'Path=Profiles/default' "$FICHIER_PROFILES")
	# 	echo "$TYPE_PROFILE"
	# 	read test
		if [ ! -z $TYPE_PROFILE ]; then
		echo "C un profil type automatik"
		else
		echo "C un profil perso on sauvegarde le fichier"
		# on recup le fichier bookmarks
		CHEMIN_BOOK=$(grep 'Path=' "$FICHIER_PROFILES" | cut -d "=" -f2 | tr -d '\r')
		cp -a ${CHEMIN_FF_CIBLE}/Firefox/${CHEMIN_BOOK}/bookmarks.html /var/se3/save/ && BOOK="ok"
		mv $FICHIER_PROFILES ${FICHIER_PROFILES}_sauve_se3_$LADATE
		fi
	fi



	echo "Déploiement du profil mozilla de $user"
	if [ "$BOOK" = "ok" ]; then
		echo "deja glop"
	else
		if [ -e $CHEMIN_CIBLE/bookmarks.html ]; then
						#echo "glop"
						cp -a $CHEMIN_CIBLE/bookmarks.html /var/se3/save/
						BOOK="ok"
		else
						BOOK="pasglop"
		fi
	fi
	rm -rf /home/$user/profil/appdata/Mozilla/Firefox/Profiles/default
	cp -a $CHEMIN_FF_SOURCE /home/$user/profil/appdata/


	if [ "$1" = "sauve_book" -a "$BOOK" = "ok" ]; then
		echo "On force l'effacement du profil par défaut, mais on sauvegarde les bookmarks."
		#echo "$CHEMIN_CIBLE/bookmarks.html"
		mv /var/se3/save/bookmarks.html  $CHEMIN_CIBLE/bookmarks.html
	fi

	# Personalisation du profil
	#if [ ! -z "$slisip" ]; then
	#	sed -e "s/slisip/$slisip/" -i $PREF_JS
	#else
	#	if [ -e /var/www/se3.pac ]; then
		# AJOUTER DETECTION IP LOCALE !!!!!!
		# /!\ !!!
	#	echo "ajout se3.pac dans la profil"
	#	SE3IP=$(/sbin/ifconfig eth0 | grep inet |cut -d : -f 2 |cut -d \  -f 1| head -n 1)
	#	sed -e "s§http://slisip/cgi-bin/slis.pac§http://$SE3IP/se3.pac§" -i $PREF_JS
	#	else
	#	sed -e "s§http://slisip/cgi-bin/slis.pac§§" -i $PREF_JS
	#	sed -e "/network.proxy.type/d" -i $PREF_JS
	#	fi
	#fi



# 
# 	if [ ! -z "$slisip" ]; then
# 		sed -e "s/slisip/$slisip/" -i $PREF_JS
# 	else
# 		sed -e "s!http://slisip/cgi-bin/slis.pac!!" -i $PREF_JS
# 		sed -e "/network.proxy.type/d" -i $PREF_JS
# 	fi
	chown -R $user:admins $CHEMIN_FF_CIBLE > /dev/null 2>&1
	chmod -R 770 $CHEMIN_FF_CIBLE > /dev/null 2>&1

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
