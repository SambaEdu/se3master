#!/bin/sh

#
## $Id$ ##
#
##### script de modif de /etc/profile afin que la machine passe par un proxy #####
# modestement ecrit par franck molle 07/2004

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Modifie /etc/profile pour ajouter la conf d'un proxy"
	echo "Sans option le proxy est supprimé"
	echo "Usage : modifProxy.sh [adresse_ip:port]"
	exit
fi	

. /usr/share/se3/includes/config.inc.sh -cms

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

for user in /home/*
do	
	if [ -e "$user"/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js ]; then 
	    rm -f "$user"/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
	    cp /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js "$user"/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
	    
	fi
	

done
