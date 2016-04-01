#!/bin/bash

## $Id: update-smbconf.sh 9249 2016-03-18 13:45:31Z keyser $ ##


# Update smb.conf based on current template version and current logon script (pl, py)
# Keep user defined shares
. /usr/share/se3/includes/config.inc.sh -cml
#. /usr/share/se3/includes/functions.inc.sh

# utf8 par défaut now
CHARSET="UTF-8"

[ ! -d /home/profiles ] && mkdir /home/profiles
chown root.root /home/profiles
chmod 777 /home/profiles
[ -z "$se3_domain" ] && se3_domain=$(grep "workgroup" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
[ -z "$netbios_name" ] && netbios_name=$(grep "netbios name" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
[ -z "$se3ip" ] && se3ip="$(expr "$(LC_ALL=C /sbin/ifconfig eth0 | grep 'inet addr')" : '.*inet addr:\([^ ]*\)')"

[ -z "$se3mask" ] && se3mask=$(grep netmask  /etc/network/interfaces | head -n1 | sed -e "s/netmask//g" | tr "\t" " " | sed -e "s/ //g")
# CHARSET=$(grep "unix charset" /etc/samba/smb.conf |grep -v "#"| head -n1 | cut -d"=" -f2 | sed -e "s/ //")
# [ -z "$CHARSET" ] && CHARSET="UTF-8"

cp -f /etc/samba/smb.conf /etc/samba/smb.conf.old
sed -e "s/#DOMAIN#/$se3_domain/g;s/#NETBIOSNAME#/$netbios_name/g;s/#IPSERVEUR#/$se3ip/g;s/#MASK#/$se3mask/g;s/#SLAPDIP#/$ldap_server/g;s/#BASEDN#/$ldap_base_dn/g;s/#ADMINRDN#/$adminRdn/g;s/#COMPUTERS#/$computersRdn/g;s/#PEOPLE#/$peopleRdn/g;s/#GROUPS#/$groupsRdn/g;s/#CHARSET#/$CHARSET/g" /var/cache/se3_install/conf/smb_3.conf.in >/etc/samba/smb.conf


if [ ! -e /etc/samba/smb_etab.conf ]; then
	echo "Analyse des partages ajoutes via l'interface par l'etablissement"
	touch /etc/samba/smb_etab.conf
	grep -A1000 "include = /etc/samba/printers_se3/%m.inc" /etc/samba/smb.conf.old | grep -v "include =" >/etc/samba/smb_etab.conf
	SMB_ETAB=$(cat /etc/samba/smb_etab.conf)
	if [ -z "$SMB_ETAB" ]; then
		echo "Attention : AUCUN partage propre a l'etablissement trouve dans le smb.conf d'origine. A noter que c'est normal si vous n'en avez jamais crees"
	else
		echo "Les partages propres a l'etablissement suivants ont ete trouve et exportes dans /etc/samba/smb_etab.conf"  
		echo "$SMB_ETAB" 
	fi
	
fi


#size=$(wc -l /root/smb.conf |cut -d ' ' -f1)
#line=$(grep -m1 -n '<.*>' /root/smb.conf |cut -d ':' -f1)
#if [ "$line" != "" ]
#then
#	tail -n $(( size - line + 1 )) /root/smb.conf | grep -v "include = " >> /etc/samba/smb_perso.conf
#fi
#rm -f /root/smb.conf




# SSL="start_tls" fix bug libnss squeeze desactivation tls
SSL="off"

if [ "$replica_status" = "2" ]
then
	SSL="off"
fi
# Pas de ssl si le ldap est local
if [ "$replica_status" == "" -o "$replica_status" = "0" ]
then	
	if [ "$ldap_server" == "$se3ip" ]
	then
		SSL="off"
	fi
fi

sed -i "s!ldap ssl.*!ldap ssl = $SSL!" /etc/samba/smb.conf
sed -i "s!recycle:repository=/home/%u/profil/Bureau/Corbeille_Reseau!recycle:repository=/home/%u/Corbeille_Reseau!" /etc/samba/smb*.conf
sed -i "s!recycle:touch !recycle:touch_mtime !" /etc/samba/smb*.conf
sed -i "s!recycle:touch=no!recycle:touch_mtime=yes!" /etc/samba/smb*.conf
/usr/share/se3/sbin/vide_corbeille.sh clean
if [ "$corbeille" == "0" ]
then
	sed -i "s/recycle:exclude=.*/recycle:exclude=\*\.\*/" /etc/samba/smb*.conf
else
    sed -i "s/recycle:exclude=\*\.\*/recycle:exclude=\?\~\$\*,\~\$\*,\*\.tmp,index\*\.pl,index\*\.htm\*,\*\.temp,\*\.TMP/" /etc/samba/smb*.conf
fi
chmod 644 /etc/samba/smb_*

/etc/init.d/samba reload >/dev/null 2>&1

echo "Test de la compatibilité ldapsam:trusted"
smbclient -L localhost -U adminse3%$xppass >/dev/null 
if [ $? = 0 ];then
	echo "Test OK"
else
	echo 'Test KO !!!'
	echo "Passage à off du paramètre ldapsam:trusted"
	sed -i 's/ldapsam:trusted = Yes/ldapsam:trusted = No/' /etc/samba/smb.conf 
	/etc/init.d/samba reload 
fi

