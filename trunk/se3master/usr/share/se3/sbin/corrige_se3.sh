#!/bin/bash

## $Id: corrige_se3.sh 3819 2009-05-04 08:11:39Z gnumdk $ ## 

# **********************************************************
# Auteur: Olivier LECLUSE
# Colorisation: 18/05/2005
# Ce script est distribué selon les termes de la licence GPL
# **********************************************************

cd /var/cache/se3_install

CONT=$1

#Couleurs - désactivation des couleurs - keyser car posant pb avec 
# lors de l'affichage ds une page web


#date et heure
LADATE=$(date +%d-%m-%Y)

# path fichier de logs
LOG_DIR="/var/log/se3"

#
# Détection de la distribution
#

# Creation de l'utilisateur www-se3 et du rep cgi-binse -- a retirer par la suite
clear

echo -e "$COLTITRE"
echo "*************************"
echo "* SCRIPT DE CORRECTION  *"
echo "*     DE SAMBAEDU3      *"
echo "*************************"

echo -e "$COLCMD\c"
cat /etc/passwd | grep www-se3 > /dev/null || ADDWWWSE3="1"

if [ "$ADDWWWSE3" = "1" ]; then
	useradd -d /var/remote_adm -s /bin/bash
	sleep 5
fi

if [ ! -d /usr/lib/cgi-binse ]; then
	mkdir /usr/lib/cgi-binse
fi
if [ ! -d /usr/share/se3/scripts ]; then
	mkdir -p /usr/share/se3/scripts
fi
if [ ! -d /usr/share/se3/sbin ]; then
	mkdir -p /usr/share/se3/sbin
fi
if [ ! -d /usr/share/se3/scripts-alertes ]; then
	mkdir -p /usr/share/se3/scripts-alertes
fi

echo -e "$COLTXT"
echo "Détection de la distribution..."
echo -e "$COLCMD\c"

if [ -e /etc/redhat-release ]; then
	DISTRIB="RH"
	WWWPATH="/var/www/html"
	APACHE="apache"
	CGIPATH="/var/www/cgi-bin"
        SMBCONF="/etc/samba/smb.conf"
	APACHECONF="/etc/httpd/conf/httpd.conf"
	PAMLDAPCONF="/etc/ldap.conf"
	NSSLDAPCONF=""
	NSSWITCH="/etc/nsswitch.conf"
	MRTGCFG="/etc/mrtg/mrtg.cfg"
        INITDSAMBA="/etc/init.d/smb"
	INITDAPACHE="/etc/init.d/httpd"
fi
if [ -e /etc/mandrake-release ]; then
	cat /etc/mandrake-release
	DISTRIB="MDK"
	WWWPATH="/var/www/html"
	APACHE="apache"
	CGIPATH="/var/www/cgi-bin"
        SMBCONF="/etc/samba/smb.conf"
	APACHECONF="/etc/httpd/conf/httpd.conf"
	PAMLDAPCONF="/etc/ldap.conf"
	NSSLDAPCONF=""
	NSSWITCH="/etc/nsswitch.conf"
	MRTGCFG="/etc/mrtg/mrtg.cfg"
        INITDSAMBA="/etc/init.d/smb"
	INITDAPACHE="/etc/init.d/httpd"
fi
if [ -e /etc/debian_version ]; then
	echo -e "$COLINFO\c"
	echo "Debian détectée, félicitation ;-)"
	DISTRIB="DEB"
	WWWPATH="/var/www"
	APACHE="www-se3"
	CGIPATH="/usr/lib/cgi-binse"
        SMBCONF="/etc/samba/smb.conf"
	APACHECONF="/etc/apache/httpdse.conf"
	PAMLDAPCONF="/etc/pam_ldap.conf"
	NSSLDAPCONF="/etc/libnss-ldap.conf"
        NSSWITCH="/etc/nsswitch.conf"
	MRTGCFG="/etc/mrtg.cfg"
        INITDSAMBA="/etc/init.d/samba"
	INITDAPACHE="/etc/init.d/apache"
fi

#
# Récupération des paramètres de connexion à la base
#

echo -e "$COLCMD\c"
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo -e "$COLERREUR"
	echo "Fichier de conf inaccessible."
	echo -e "$COLTXT"
	exit 1
fi
LDAPIP=`echo "SELECT value FROM params WHERE name='ldap_server'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$LDAPIP" ]; then
	echo -e "$COLERREUR"
	echo "Impossible d'accéder aux paramètres SambaEdu."
	echo -e "$COLTXT"
	exit 1
fi

cp -ax /var/cache/se3_install/conf/sudoers /etc/sudoers
chmod 0440 /etc/sudoers


#
# Mise à jour des scripts et de l'interface
#
echo -e "$COLTXT"
echo "Mise à jour de l'interface..."
echo -e "$COLCMD\c"
tar -zxf wwwse3.tgz -C $WWWPATH 2>/dev/null

# Supprerssion du menu sauvegarde
if [ ! -d /etc/backuppc ]; then
	rm /var/www/se3/includes/menu.d/95sauvegarde.inc
fi

# Mise en place de la protection sur le dossier setup
# rm $WWWPATH/se3/setup/index.php
# ln $WWWPATH/se3/edit_params.php $WWWPATH/se3/setup/index.php
# echo "AuthUserFile $WWWPATH/se3/setup/.htpasswd" >> $WWWPATH/se3/setup/.htaccess

if [ ! -z $(grep "3.1" /etc/debian_version) ]; then
adminpass=`getent passwd admin | cut -d: -f2`
	if [ "$adminpass" = "x" ]; then
		adminpass=`getent shadow admin | cut -d: -f2`
	fi
echo "admin:$adminpass" > $WWWPATH/se3/setup/.htpasswd
chgrp root $WWWPATH/se3/setup/ -R
chmod 750 $WWWPATH/se3/setup/ -R
fi

chown -R $APACHE $WWWPATH/se3
cp -a gepcgi/gep*.cgi $CGIPATH
chown $APACHE $CGIPATH/gep*.cgi
cp -a gepcgi/Se.pm /usr/lib/perl5
chown $APACHE /usr/lib/perl5/Se.pm
chown $APACHE scripts/*
cp -a scripts/* /usr/share/se3/sbin
chown $APACHE sudoscripts/*
cp -a sudoscripts/* /usr/share/se3/scripts
cp -a scripts-alertes/* /usr/share/se3/scripts-alertes

cp -ax /var/cache/se3_install/conf/se3-logrotate /etc/logrotate.d/se3

# Mise a jour de l'exe de fond d'ecran
cp -a reg/fde.exe /home/netlogon
cp -a reg/killexplorer.exe /home/netlogon
cp -a reg/majdll.exe /home/netlogon
chown admin.admins /home/netlogon/

#
# Rétablissement du script mkslurpd
#
echo -e "$COLCMD\c"
mv /usr/share/se3/sbin/mkslurpd /usr/share/se3/sbin/mkslurpd.old
cat /usr/share/se3/sbin/mkslurpd.old | sed -e "s/#MYSQLIP#/$dbhost/g" | sed -e "s/#SE3DBPASS#/$dbpass/g" > /usr/share/se3/sbin/mkslurpd
rm  /usr/share/se3/sbin/mkslurpd.old
chmod 750  /usr/share/se3/sbin/mkslurpd
chown root.root  /usr/share/se3/sbin/mkslurpd

/usr/share/se3/sbin/update-share.sh -v -d

VERSION=`cat /var/cache/se3_install/version`
echo "DELETE FROM params WHERE name=\"version\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
echo "INSERT INTO params (\`name\`,\`value\`,\`descr\`,\`cat\`) VALUES ('version',\"$VERSION\",'No de version','4')"| mysql -h $dbhost $dbname -u $dbuser -p$dbpass  	
echo "UPDATE params SET value=\"$MAJNBR\" WHERE name=\"majnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
echo -e "$COLTXT"

cd -
