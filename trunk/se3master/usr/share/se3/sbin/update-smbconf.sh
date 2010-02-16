#!/bin/bash
# Update smb.conf based on current template version and current logon script (pl, py)
# Keep user defined shares
. /usr/share/se3/includes/config.inc.sh -cml
#. /usr/share/se3/includes/functions.inc.sh

[ ! -d /home/profiles ] && mkdir /home/profiles
chown root.root /home/profiles
chmod 777 /home/profiles
[ -z "$se3_domain" ] && se3_domain=$(grep "workgroup" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
[ -z "$netbios_name" ] && netbios_name=$(grep "netbios name" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
[ -z "$se3ip" ] && se3ip="$(expr "$(LC_ALL=C /sbin/ifconfig eth0 | grep 'inet addr')" : '.*inet addr:\([^ ]*\)')"

MASK=$(grep netmask  /etc/network/interfaces | head -n1 | sed -e "s/netmask//g" | tr "\t" " " | sed -e "s/ //g")
CHARSET=$(grep "unix charset" /etc/samba/smb.conf | cut -d"=" -f2 | sed -e "s/ //")
[ -z "$CHARSET" ] && CHARSET="UTF-8"

cp -f /etc/samba/smb.conf /tmp
sed -e "s/#DOMAIN#/$se3_domain/g;s/#NETBIOSNAME#/$netbios_name/g;s/#IPSERVEUR#/$se3ip/g;s/#MASK#/$MASK/g;s/#SLAPDIP#/$ldap_server/g;s/#BASEDN#/$ldap_base_dn/g;s/#ADMINRDN#/$adminRdn/g;s/#COMPUTERS#/$computersRdn/g;s/#PEOPLE#/$peopleRdn/g;s/#GROUPS#/$groupsRdn/g;s/#CHARSET#/$CHARSET/g" /var/cache/se3_install/conf/smb_3.conf.in >/etc/samba/smb.conf
size=$(wc -l /tmp/smb.conf |cut -d ' ' -f1)
line=$(grep -m1 -n '<.*>' /tmp/smb.conf |cut -d ':' -f1)
if [ "$line" != "" ]
then
	tail -n $(( size - line + 1 )) /tmp/smb.conf >> /etc/samba/smb.conf
fi
rm -f /tmp/smb.conf

SSL="start_tls"
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
if [ "$corbeille" == "0" ]
then
	sed -i "s/recycle:exclude=.*/recycle:exclude=\*\.\*/" /etc/samba/smb*.conf
fi

chmod 644 /etc/samba/smb_*

/etc/init.d/samba reload >/dev/null 2>&1
