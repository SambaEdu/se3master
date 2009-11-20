#!/bin/bash
# Update smb.conf based on current template version and current logon script (pl, py)
# Keep user defined shares

# Get mysql conf
dbhost=$(expr "$(grep mysqlServerIp /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbuser=$(expr "$(grep mysqlServerUsername /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbpass=$(expr "$(grep mysqlServerPw /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbname=$(expr "$(grep connexionDb /etc/SeConfig.ph)" : ".*'\(.*\)'.*")

[ ! -d /home/profiles ] && mkdir /home/profiles
chown root.root /home/profiles
chmod 777 /home/profiles

NTDOM=$(grep "workgroup" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
NETBIOS=$(grep "netbios name" /etc/samba/smb.conf|cut -d '=' -f2|sed -e 's/ //g')
SE3IP="$(expr "$(LC_ALL=C /sbin/ifconfig eth0 | grep 'inet addr')" : '.*inet addr:\([^ ]*\)')"
MASK="$(expr "$(LC_ALL=C /sbin/ifconfig eth0 | grep Mask)" : '.*Mask:\(.*\)')"
LDAPIP=`echo "SELECT value FROM params WHERE name='ldap_server'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
COMPUTERSRDN=`echo "SELECT value FROM params WHERE name='computersRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
GROUPSRDN=`echo "SELECT value FROM params WHERE name='groupsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
REPLICA_STATUS=`echo "SELECT value FROM params WHERE name='replica_status'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
CORBEILLE=`echo "SELECT value FROM params WHERE name='corbeille'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

cp -f /etc/samba/smb.conf /tmp
sed -e "s/#DOMAIN#/$NTDOM/g;s/#NETBIOSNAME#/$NETBIOS/g;s/#IPSERVEUR#/$SE3IP/g;s/#MASK#/$MASK/g;s/#SLAPDIP#/$LDAPIP/g;s/#BASEDN#/$BASEDN/g;s/#ADMINRDN#/$ADMINRDN/g;s/#COMPUTERS#/$COMPUTERSRDN/g;s/#PEOPLE#/$PEOPLERDN/g;s/#GROUPS#/$GROUPSRDN/g" /var/cache/se3_install/conf/smb_3.conf.in >/etc/samba/smb.conf
size=$(wc -l /tmp/smb.conf |cut -d ' ' -f1)
line=$(grep -m1 -n '<.*>' /tmp/smb.conf |cut -d ':' -f1)
if [ "$line" != "" ]
then
	tail -n $(( size - line + 1 )) /tmp/smb.conf >> /etc/samba/smb.conf
fi
rm -f /tmp/smb.conf

SSL="start_tls"
if [ "$REPLICA_STATUS" = "2" ]
then
	SSL="off"
fi
# Pas de ssl si le ldap est local
if [ "$REPLICA_STATUS" == "" -o "$REPLICA_STATUS" = "0" ]
then	
	if [ "$LDAPIP" == "$SE3IP" ]
	then
		SSL="off"
	fi
fi

sed -i "s!ldap ssl.*!ldap ssl = $SSL!" /etc/samba/smb.conf
if [ "$CORBEILLE" == "0" ]
then
	sed -i "s!recycle:exclude=.*!recycle:exclude=\*\.\*!" /etc/samba/smb*.conf
fi

chmod 644 /etc/samba/smb_*

/etc/init.d/samba reload >/dev/null 2>&1
