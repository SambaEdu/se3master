#!/bin/bash
## $Id: mkhome.sh 3544 2009-02-15 00:00:33Z keyser $ ##
#shares_Win95: homes
#shares_Win2K: homes
#shares_WinXP: homes
#shares_Vista: homes
#action: start
#level: 09
#
#
##### Cr�e le r�pertoire personnel de user #####
#
#


if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Cr�e le r�pertoire personnel de user"
	echo "Usage : mkhome.sh user"
fi	
	
user=$1
# Creation du repertoire perso le cas echeant
# -------------------------------------------
if [ ! -d "/home/$user" -o ! -d "/home/$user/profil" ]; then

    . /etc/se3/config_c.cache.sh
	. /etc/se3/config_m.cache.sh
 	. /etc/se3/config_o.cache.sh
	. /etc/se3/config_p.cache.sh
	
    [ -d "/home/$user" ] || mkdir /home/$user
    cp -a $path2UserSkel/* /home/$user > /dev/null # 2>&1

	# kz - Ajout pour la construction du fichier de pref de moz TB uniquement car FF g�r� lors des maj
	# 
	PREF_JS_TB="/home/$user/profil/appdata/Thunderbird/Profiles/default/prefs.js"
# 	PREF_JS_FF="/home/$user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js"
	
	
	SlisIp=`echo "SELECT value FROM params WHERE name='slisip'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
# 	if [ ! -z "$slissp" ]; then
# 	sed -e "s/slisip/$slisip/" -i $PREF_JS_FF
# 	else
# 		if [ -e /var/www/se3.pac ]; then
# 		
# 		sed -e "s�http://slisip/cgi-bin/slis.pac�http://$se3ip/se3.pac�" -i $PREF_JS_FF
# 		else
# 		sed -e "s�http://slisip/cgi-bin/slis.pac��" -i $PREF_JS_FF
# 		sed -e "/network.proxy.type/d" -i $PREF_JS_FF
# 		fi
# 	fi
	
	MAIL=`ldapsearch -xLLL "uid=$user" | grep mail | cut -d " " -f2`
	PRENOM=`ldapsearch -xLLL "uid=$user" | grep gecos | cut -d " " -f2`
	NOM=`ldapsearch -xLLL "uid=$user" | grep gecos | cut -d " " -f3 | cut -d "," -f1`
	DOMNAME=`ldapsearch -xLLL "uid=$user" | grep mail | cut -d " " -f2 | cut -d "@" -f2`
	
	
	if [ -z "$lcsIp" ]; then
		cat "/home/$user/profil/appdata/Thunderbird/Profiles/default/prefs.js.slis"  \
		| sed -e "s/nom_compte_replace@domaine/$MAIL/g" \
		| sed -e "s/nom_compte_replace/$user/g" \
		| sed -e "s/domaine/$DOMNAME/g" \
		| sed -e "s/pop.replace.fr/$DOMNAME/g" \
		| sed -e "s/smtp.replace.fr/$DOMNAME/g" \
		| sed -e "s/votre_nom_replace/$PRENOM\ $NOM/g" \
		| sed -e "s/login_replace/$user/g" >  $PREF_JS_TB
		
	
	else
		cat "/home/$user/profil/appdata/Thunderbird/Profiles/default/prefs.js.lcs" \
		| sed -e "s/nom_compte_replace@domaine/$MAIL/g" \
		| sed -e "s/nom_compte_replace/$user/g" \
		| sed -e "s/domaine/$DOMNAME/g" \
		| sed -e "s/pop.replace.fr/$DOMNAME/g" \
		| sed -e "s/smtp.replace.fr/$DOMNAME/g" \
		| sed -e "s/votre_nom_replace/$PRENOM\ $NOM/g" \
		| sed -e "s/nom_serveur_replace/$lcsIp/g" \
		| sed -e "s/login_replace/$user/g" >  $PREF_JS_TB
	
	fi
	
	chown -R $user:admins /home/$user > /dev/null 2>&1
	chmod -R 700 /home/$user > /dev/null 2>&1
	setfacl -R -m d:u:$user:rwx /home/$user


else
	useruid=`getent passwd $user | gawk -F ':' '{print $3}'`
	prop=`stat -c%u /home/$user`
	if [ ! "$prop" = "$useruid" ]; then
	chown -R $user:admins /home/$user > /dev/null 2>&1
	chown -R root:admins /home/$user/profil/Bureau/* > /dev/null 2>&1
	chown -R root:admins /home/$user/profil/Demarrer/* > /dev/null 2>&1
	fi 
fi
