#!/bin/bash
. /usr/share/se3/includes/config.inc.sh -cml
#. /usr/share/se3/includes/functions.inc.sh
debian_vers=$(cat /etc/debian_version)
[ -z "$netbios_name" ] && netbios_name=$(grep "netbios name" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
[ -z "$se3ip" ] && se3ip="$(LC_ALL=C grep address /etc/network/interfaces | sort | head -n1 | cut -d" " -f2)"
MASK=$(grep netmask  /etc/network/interfaces | head -n1 | sed -e "s/netmask//g" | tr "\t" " " | sed -e "s/ //g")

if [ -z "$adminPw" ]; then
	echo "impossible de lire la parametre adminPw"
	exit 1
fi

# Get network card
ECARD="$(grep iface /etc/network/interfaces | grep static | sort | head -n1 | awk '{print $2}')"
if [ -z "$ECARD" ]; then
ECARD=$(/sbin/ifconfig -a | grep eth | sort | head -n 1 | cut -d " " -f 1)
fi

#Mise a l'heure du serveur
if [ ! -z "$slisIp" ]; then
echo "Mise a l'heure sur le slis via ntp"
ntpdate $slisIp 
else

	if [ ! -z "$ntpserv" ]; then
		echo "Mise a l'heure sur le serveur ntp declare dans l'interface"
		ntpdate $ntpserv
	else
		echo "Pas de serveur ntp declare dasn l'interface, Mise a l'heure sur le serveur de creteil"
		ntpdate ntp.ac-creteil.fr
	fi

fi

# openldap pass
echo $adminPw >/etc/ldap.secret

# Conf firefox
if [ ! -f /var/se3/unattended/install/packages/firefox/firefox-profile.js ]; then 
    cp -f /var/se3/unattended/install/packages/firefox/firefox-profile.ori /var/se3/unattended/install/packages/firefox/firefox-profile.js
fi

# Mrtg
sed -i "s/eth0/$ECARD/g" /etc/mrtg.cfg
sed -i "s/growright//g" /etc/mrtg.cfg
indexmaker --output=/var/www/se3/sysmon/index.html /etc/mrtg.cfg
env LANG=C /usr/bin/mrtg /etc/mrtg.cfg 2>/dev/null
env LANG=C /usr/bin/mrtg /etc/mrtg.cfg 2>/dev/null
env LANG=C /usr/bin/mrtg /etc/mrtg.cfg 2>/dev/null

# Build Encode::compat
(
cd /usr/src/Encode-compat-0.05
perl Makefile.PL
make
make install
cd -
) >/dev/null
(
# Apache2se
update-rc.d -f apache2se remove .
update-rc.d apache2se defaults 91 91

# Admind
/etc/init.d/admind stop
update-rc.d -f admind remove .
update-rc.d admind defaults 99 99
/etc/init.d/admind start

# SSH
update-rc.d -f ssh remove .
update-rc.d ssh defaults 16 16
) >/dev/null

# Cron
#/etc/init.d/cron restart ---> plus bas

# Logs
touch /var/log/se3/auth.log

# Wget
cat /etc/wgetrc |grep "passive_ftp = on" > /dev/null || echo "passive_ftp = on" >> /etc/wgetrc

# Remote adm
if [ ! -e /var/remote_adm/.ssh/id_rsa.pub ]; then
	chown www-se3 -R /var/remote_adm
	su www-se3 -c 'ssh-keygen -t rsa -f /var/remote_adm/.ssh/id_rsa -N ""'
fi

# Mkslurpd
sed -i "s/#MYSQLIP#/$MYSQLIP/g;s/#SE3DBPASS#/$SE3PW/g" /usr/share/se3/sbin/mkslurpd

# Syslog
if [ "$debian_vers" == "4.0" ]; then
	/etc/init.d/sysklogd restart 
else
	/etc/init.d/rsyslog restart
fi

echo "update de la configuration samba"
smbpasswd -w $adminPw
/usr/share/se3/sbin/update-smbconf.sh

# Shares conf
/usr/share/se3/sbin/update-share.sh -d

# Slave
SLAVE_TEST=`ldapsearch -xLLL "(&(objectclass=*)(l=esclave))"`
if [ ! -z "$SLAVE_TEST" ]; then
    echo "Esclave!"
	mv -f /var/www/se3/includes/menu.d/.51ressources.inc  /var/www/se3/includes/menu.d/51ressources.inc
fi

# Templates
for template in base admin eleves profs
do
	for logon in /home/templates/$template/.logon*.bat
	do
		dst=$(expr $logon : '.*\.\([^/]*\).bat')
		if [ ! -f /home/templates/$template/$dst.bat ]
		then
			sed -e "s/%se3pdc%/$netbios_name/g" $logon >/home/templates/$template/$dst.bat
		fi
	done
done



# Backuppc

cd /usr/share/backuppc/lib/BackupPC/CGI
rm -f Lib.pm
if [ "$debian_vers" == "4.0" ]; then
	ln -s Lib.pm.etch Lib.pm
else
	ln -s Lib.pm.lenny Lib.pm
fi
cd - >/dev/null


# Firefox
PREF_JS_FF="/etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js"
sed -i "s/%ip%/$se3ip/g;s/%se3pdc%/$(hostname -f)/g" /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/hostperm.1
if [ "$slisIp" == "" ]
then
	if [ -e /var/www/se3.pac ]; then
		sed -i 's/%proxytype%/2/g' $PREF_JS_FF
		sed -i "s/%proxyurl%/http:\/\/$se3ip\/se3.pac/g" $PREF_JS_FF
	else
		if [ -z $http_proxy ]
		then
			sed -i 's/%proxytype%/0/g' $PREF_JS_FF
		else
			sed -i 's/%proxytype%/1/g' $PREF_JS_FF
		proxyurl=$(echo $http_proxy|sed -e 's/http:\/\///g')
			sed -i "s/%proxyurl%/$proxyurl/g" $PREF_JS_FF
		fi	
	fi	
	
else
		
		sed -i 's/%proxytype%/2/g' $PREF_JS_FF
		sed -i "s/%proxyurl%/http:\/\/$slisIp\/cgi-bin\/slis.pac/g" $PREF_JS_FF
fi
/etc/init.d/cron reload

# Corrige icone reparer son compte si L: toujours utilise pour Progs
grep W: /home/templates/base/logon.bat >/dev/null || cp -f /var/cache/se3_install/conf/Reparer\ son\ compte_legacy.lnk /home/templates/base/Bureau/Reparer\ son\ compte.lnk
