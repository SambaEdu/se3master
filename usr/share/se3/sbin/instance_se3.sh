#!/bin/bash

dbhost=$(expr "$(grep mysqlServerIp /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbuser=$(expr "$(grep mysqlServerUsername /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbpass=$(expr "$(grep mysqlServerPw /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbname=$(expr "$(grep connexionDb /etc/SeConfig.ph)" : ".*'\(.*\)'.*")

NTDOM=$(grep "workgroup" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
NETBIOS=$(grep "netbios name" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
SE3IP="$(expr "$(LC_ALL=C /sbin/ifconfig eth0 | grep 'inet addr')" : '.*inet addr:\([^ ]*\)')"
MASK="$(expr "$(LC_ALL=C /sbin/ifconfig eth0 | grep Mask)" : '.*Mask:\(.*\)')"
LDAPIP=`echo "SELECT value FROM params WHERE name='ldap_server'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
COMPUTERSRDN=`echo "SELECT value FROM params WHERE name='computersRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
GROUPSRDN=`echo "SELECT value FROM params WHERE name='groupsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SLISIP=`echo "SELECT value FROM params WHERE name='slisIp'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
REPLICA_IP=`echo "SELECT value FROM params WHERE name='replica_ip'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

# Get network card
ECARD=$(/sbin/ifconfig | grep eth | sort | head -n 1 | cut -d " " -f 1)
if [ -z "$ECARD" ]; then
ECARD=$(/sbin/ifconfig -a | grep eth | sort | head -n 1 | cut -d " " -f 1)
fi

# openldap pass
echo $ADMINPW >/etc/ldap.secret

# Conf firefox
[ ! -f /var/se3/unattended/install/packages/firefox/firefox-profile.js ] && (
    cp -f /var/se3/unattended/install/packages/firefox/firefox-profile.ori /var/se3/unattended/install/packages/firefox/firefox-profile.js
)

# Mrtg
sed -i "s/eth0/$ECARD/g" /etc/mrtg.cfg
sed -i "s/growright//g" /etc/mrtg.cfg
indexmaker --output=/var/www/se3/sysmon/index.html /etc/mrtg.cfg
env LANG=C /usr/bin/mrtg /etc/mrtg.cfg 2>/dev/null
env LANG=C /usr/bin/mrtg /etc/mrtg.cfg 2>/dev/null
env LANG=C /usr/bin/mrtg /etc/mrtg.cfg 2>/dev/null

# Build Encode::compat
cd /usr/src/Encode-compat-0.05
perl Makefile.PL
make
make install
cd -

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
) 2>/dev/null

# Cron
/etc/init.d/cron restart

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
/etc/init.d/sysklogd restart 2>/dev/null

# Smb conf
smbpasswd -w $ADMINPW
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
			sed -e "s/%se3pdc%/$NETBIOS/g" $logon >/home/templates/$template/$dst.bat
		fi
	done
done

# Firefox
sed -i "s/%ip%/$SE3IP/g;s/%se3pdc%/$(hostname -f)/g" /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/hostperm.1
if [ "$SLISIP" == "" ]
then
	if [ -z $http_proxy ]
	then
		sed -i 's/%proxytype%/0/g' /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
	else
		sed -i 's/%proxytype%/1/g' /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
		proxyurl=$(echo $http_proxy|sed -e 's/http:\/\///g')
		sed -i "s/%proxyurl%/$proxyurl/g" /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
	fi
else
		
		sed -i 's/%proxytype%/2/g' /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
		sed -i "s/%proxyurl%/http:\/\/$SLISIP\/cgi-bin\/slis.pac/g" /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js
fi
/etc/init.d/cron reload

# Corrige icone reparer son compte si L: toujours utilise pour Progs
grep W: /home/templates/base/logon.bat >/dev/null || cp -f /var/cache/se3_install/conf/Réparer\ son\ compte_legacy.lnk /home/templates/base/Bureau/Réparer\ son\ compte.lnk
