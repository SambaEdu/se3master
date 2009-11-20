#!/bin/bash
#
# Récupération des paramètres de connexion à la base
#

WWWPATH="/var/www"
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
        dbhost=`/bin/cat $WWWPATH/se3/includes/config.inc.php | /bin/grep "dbhost=" | /usr/bin/cut -d = -f 2 |/usr/bin/cut -d \" -f 2`
        dbname=`/bin/cat $WWWPATH/se3/includes/config.inc.php | /bin/grep "dbname=" | /usr/bin/cut -d = -f 2 |/usr/bin/cut -d \" -f 2`
        dbuser=`/bin/cat $WWWPATH/se3/includes/config.inc.php | /bin/grep "dbuser=" | /usr/bin/cut -d = -f 2 |/usr/bin/cut -d \" -f 2`
        dbpass=`/bin/cat $WWWPATH/se3/includes/config.inc.php | /bin/grep "dbpass=" | /usr/bin/cut -d = -f 2 |/usr/bin/cut -d \" -f 2`
else
       echo "Fichier de conf inaccessible"
       exit 1
fi

SMBVERSION=`echo "SELECT value FROM params WHERE name='smbversion'" | /usr/bin/mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
XPPASS=`echo "SELECT value FROM params WHERE name='xppass'" | /usr/bin/mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
DOMAIN=`cat /etc/samba/smb.conf |grep -i workgroup | gawk -F ' = ' '{print $2}'`
IPSE3=`cat /etc/samba/smb.conf |grep -i interfaces | awk -F' = ' '{ print $2 }'|cut -d '/' -f 1`
NETBIOS=`cat /etc/samba/smb.conf |grep -i netbios | awk -F' = ' '{ print $2 }'`

# if [ "$SMBVERSION" = "samba3" ]; then
# 	NEWPASS="wawa"
# else
# 	NEWPASS=`/bin/date | /usr/bin/md5sum | /usr/bin/cut -c 1-6`
# fi
NEWPASS=`/bin/date | /usr/bin/md5sum | /usr/bin/cut -c 1-6`
XPPASS1=`echo "SELECT value FROM params WHERE name='xppass'" | mysql -h localhost se3db -N | md5sum | cut -c 3-8`
#
# Creation/cgt pass du user unattend
#
getent passwd unattend >/dev/null && UNAT=1
if [ "$UNAT" = "1" ]; then
  /usr/share/se3/sbin/userChangePwd.pl unattend $XPPASS1
else
  # Creation user unattend
  UIDPOLICY=`echo "SELECT value FROM params WHERE name='uidPolicy'" | mysql -h localhost se3db -N`
  echo "UPDATE params SET value='4' WHERE name='uidPolicy'" | mysql -h localhost se3db
  /usr/share/se3/sbin/userAdd.pl d unatten $XPPASS1 00000000 M Administratifs
  echo "UPDATE params SET value=\"$UIDPOLICY\" WHERE name='uidPolicy'" | mysql -h localhost se3db
fi
# Mise en place des droits
chmod -R 755 /var/se3/unattended
[ -e /var/se3/unattended/install/computers ] && (
	setfacl -m u:unattend:rxw /var/se3/unattended/install/computers
	setfacl -m d:u:unattend:rxw /var/se3/unattended/install/computers
	setfacl -R -m u:www-se3:rx /var/se3/unattended/install/computers
	setfacl -R -m d:u:www-se3:rx /var/se3/unattended/install/computers
)
[ -e /var/se3/unattended/install/packages ] && (
	setfacl -R -m u:unattend:rx /var/se3/unattended/install/packages
	setfacl -R -m d:u:unattend:rx /var/se3/unattended/install/packages
	setfacl -R -m u:www-se3:rx /var/se3/unattended/install/packages
	setfacl -R -m d:u:www-se3:rx /var/se3/unattended/install/packages
)

CRYPT=`/usr/share/se3/sbin/cryptcons $XPPASS1`
CRYPT=$(echo $CRYPT| sed "s/\\//\\\\\//")
/bin/cat /var/se3/Progs/install/installdll/confse3.in | /bin/sed -e "s/#XPPASS#/$XPPASS/g" | /bin/sed -e "s/#DOMAIN#/$DOMAIN/g" | /bin/sed -e "s/#IPSE3#/$IPSE3/g" | /bin/sed -e "s/#NETBIOS#/$NETBIOS/g" | /bin/sed -e "s/#WAWA#/$NEWPASS/g" > /var/se3/Progs/install/installdll/confse3.ini
/bin/cat /home/netlogon/registre.vbs.in | /bin/sed -e "s/#SE3#/$NETBIOS/g" | /bin/sed -e "s/#DOMAIN#/$DOMAIN/g" > /home/netlogon/registre.vbs
/bin/cat /var/se3/Progs/install/installdll/installcop.ba | /bin/sed -e "s/#IPSE3#/$IPSE3/g" > /var/se3/Progs/install/installdll/installcop.bat
/bin/sed -e "s/#NETBIOS#/$NETBIOS/g" -i /var/se3/Progs/install/installdll/installcop.bat
/bin/cat /var/se3/Progs/install/installdll/todo.cm | /bin/sed -e "s/#NETBIOS#/$NETBIOS/g" | /bin/sed -e "s/#PWD#/$CRYPT/g" > /var/se3/Progs/install/installdll/todo.cmd
if [ ! "$SMBVERSION" = "samba3" ]; then
	/usr/bin/smbpasswd root $NEWPASS
else
echo -e "${NEWPASS}\n$NEWPASS" | (/usr/bin/smbpasswd -s root)

fi

# Remise en place des droits

/bin/chmod 771 /var/se3/Progs/install
/usr/bin/setfacl -m o::rx /var/se3/Progs/install/installdll/
/usr/bin/setfacl -m o::r /var/se3/Progs/install/installdll/*MSI*
/usr/bin/setfacl -m o::r /var/se3/Progs/install/installdll/*.vbs
/usr/bin/setfacl -m o::- /var/se3/Progs/install/installdll/*.ini
