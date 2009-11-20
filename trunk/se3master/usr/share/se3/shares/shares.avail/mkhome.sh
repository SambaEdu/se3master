#!/bin/bash

## $Id: mkhome.pl 3544 2009-02-15 00:00:33Z keyser $ ##
#shares_Win95: homes
#shares_Win2K: homes
#shares_WinXP: homes
#shares_Vista: homes
#action: start
#level: 09
#
#
##### Crée le répertoire personnel de user #####
#
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Crée le répertoire personnel de user"
	echo "Usage : mkhome.pl user"
fi	
	
user=$1
# Creation du repertoire perso le cas echeant
# -------------------------------------------
if [ ! -d "/home/$user" ]; then
	WWWPATH="/var/www"
	
	if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
		dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
		dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
		dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
		dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
	fi
	
	path2UserSkel=`echo "SELECT value FROM params WHERE name='path2UserSkel'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N || echo "pb avec mysql"; path2UserSkel="/etc/skel/user"`
	if [ -z "$path2UserSkel" ]; then
		path2UserSkel="/etc/skel/user"
	fi
	lcsIp=`echo "SELECT value FROM params WHERE name='lcsIp'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	
	cp -a $path2UserSkel/ /home/$user > /dev/null 2>&1
	
	# kz - Ajout pour la construction du fichier de pref de moz TB et fichier de moz FF
	# 
	PREF_JS_TB="/home/$user/profil/appdata/Thunderbird/Profiles/default/prefs.js"
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
	prop=`ls -nld /home/$user | gawk -F ' ' '{print $3}'`
	if [ ! "$prop" = "$useruid" ]; then
	chown -R $user:admins /home/$user > /dev/null 2>&1
	chown -R root:admins /home/$user/profil/Bureau/* > /dev/null 2>&1
	chown -R root:admins /home/$user/profil/Demarrer/* > /dev/null 2>&1
	fi 
fi
