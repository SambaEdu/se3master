#!/bin/bash
. /usr/share/se3/includes/config.inc.sh -cml
#. /usr/share/se3/includes/functions.inc.sh
debian_vers=$(cat /etc/debian_version)
[ -z "$netbios_name" ] && netbios_name=$(grep "netbios name" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
[ -z "$se3ip" ] && se3ip="$(LC_ALL=C grep address /etc/network/interfaces | sort | head -n1 | cut -d" " -f2)"
[ -z "$ecard" ] &&  ecard="$(grep iface /etc/network/interfaces | grep static | sort | head -n1 | awk '{print $2}')"
if [ -z "$adminPw" ]; then
	echo "impossible de lire le parametre adminPw"
	exit 1
fi

# Get network card
# 
# if [ -z "$ECARD" ]; then
# ECARD=$(/sbin/ifconfig -a | grep eth | sort | head -n 1 | cut -d " " -f 1)
# fi

#Mise a l'heure du serveur
if [ ! -z "$slisip" ]; then
echo "Mise a l'heure sur le slis via ntp"
ntpdate $slisip 
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
sed -i "s/eth0/$ecard/g" /etc/mrtg.cfg
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

# Mkslurpd ---> deprecated a vi
#sed -i "s/#MYSQLIP#/$MYSQLIP/g;s/#SE3DBPASS#/$SE3PW/g" /usr/share/se3/sbin/mkslurpd

# Syslog
if [ -e /etc/init.d/sysklogd ]; then
	/etc/init.d/sysklogd restart 
else
	/etc/init.d/rsyslog restart
fi

echo "update de la configuration samba"
smbpasswd -w $adminPw
/usr/share/se3/sbin/update-smbconf.sh

# Shares conf
/usr/share/se3/sbin/update-share.sh -d



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


# Firefox
if [ ! -e /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js ]; then
    proxyurl=$(grep http_proxy /etc/profile | grep -v export | cut -d= -f2 | sed -e "s/http:\/\///;s/\"//g")

    sed -i "s/%ip%/$se3ip/g;s/%se3pdc%/$(hostname -f)/g" /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/hostperm.1
    /usr/share/se3/scripts/modifProxy.sh $proxyurl

fi
/etc/init.d/cron reload

#/etc/init.d/slapd restart
/var/cache/se3_install/depmaj/install_supervision_rouen.sh 

# Corrige icone reparer son compte si L: toujours utilise pour Progs
grep W: /home/templates/base/logon.bat >/dev/null || cp -f /var/cache/se3_install/conf/Reparer\ son\ compte_legacy.lnk /home/templates/base/Bureau/Reparer\ son\ compte.lnk
/usr/share/se3/includes/config.inc.sh -clpbmsdf
