#!/bin/bash

# **********************************************************
# Désinstallation de SambaEdu3 Version 0.1
# 22 Juillet 2002
# Auteur: Olivier LECLUSE
# Ce script est diftribué selon les termes de la licence GPL
# **********************************************************

clear
echo "Ce script détruira toute trace de SambaEdu!!"
echo "Pour poursuivre, tapez \"Je suis SUR\""
read rep
if [ ! "$rep" = "Je suis SUR" ]; then
	echo "Abandon de la désinstallation"
exit 0
fi

#
# Détection de la distribution
#

clear
echo "Détection de la distribution"

if [ -e /etc/redhat-release ]; then
	cat /etc/redhat-release
	DISTRIB="RH"
	WWWPATH="/var/www/html"
	CGIPATH="/var/www/cgi-bin"
	APACHE="apache"
	LDAPGRP="ldap"
	SMBCONF="/etc/samba/smb.conf"
	SLAPDIR="openldap"
	SLAPDCONF="/etc/$SLAPDIR/slapd.conf"
	PAMLDAPCONF="/etc/ldap.conf"
	NSSLDAPCONF=""
	NSSWITCH="/etc/nsswitch.conf"
	INITDSAMBA="/etc/init.d/smb"
	INITDAPACHE="/etc/init.d/httpd"
	INITDSLAPD="/etc/init.d/ldap"
	INITDNSCD="/etc/init.d/nscd"
fi
if [ -e /etc/mandrake-release ]; then
	cat /etc/mandrake-release
	DISTRIB="MDK"
	WWWPATH="/var/www/html"
	CGIPATH="/var/www/cgi-bin"
	APACHE="apache"
	LDAPGRP="ldap"
	SMBCONF="/etc/samba/smb.conf"
	SLAPDIR="openldap"
	SLAPDCONF="/etc/$SLAPDIR/slapd.conf"
	PAMLDAPCONF="/etc/ldap.conf"
	NSSLDAPCONF=""
	NSSWITCH="/etc/nsswitch.conf"
	INITDSAMBA="/etc/init.d/smb"
	INITDAPACHE="/etc/init.d/httpd"
	INITDSLAPD="/etc/init.d/ldap"
	INITDNSCD="/etc/init.d/nscd"
fi
if [ -e /etc/debian_version ]; then
	echo "Debian détectée, félicitation ;-)"
	DISTRIB="DEB"
	WWWPATH="/var/www"
	CGIPATH="/usr/lib/cgi-bin"
	APACHE="www-data"
	LDAPGRP="root"
	SMBCONF="/etc/samba/smb.conf"
	SLAPDIR="ldap"
	SLAPDCONF="/etc/$SLAPDIR/slapd.conf"
	PAMLDAPCONF="/etc/pam_ldap.conf"
	NSSLDAPCONF="/etc/libnss-ldap.conf"
	NSSWITCH="/etc/nsswitch.conf"
	INITDSAMBA="/etc/init.d/samba"
	INITDAPACHE="/etc/init.d/apache"
	INITDSLAPD="/etc/init.d/slapd"
	INITDNSCD="/etc/init.d/nscd"
fi

$INITDSAMBA stop
$INITDSLAPD stop
$INITDSAMBA stop

/bin/rm -r /usr/share/se3
/bin/rm $WWWPATH/se3 -r
/bin/rm $CGIPATH/gep.cgi
/bin/rm /etc/SeConfig.ph
/bin/rm /usr/lib/perl5/Se.pm
/bin/rm /var/se3 -r

/bin/mv $SMBCONF.se3sav $SMBCONF
/bin/mv /etc/$SLAPDIR/ldap.conf.se3sav /etc/$SLAPDIR/ldap.conf
/bin/mv $SLAPDCONF.se3sav $SLAPDCONF
/bin/mv $PAMLDAPCONF.se3sav $PAMLDAPCONF
if [ "$DISTRIB" = "deb" ]; then
	/bin/mv $NSSLDAPCONF.se3sav $NSSLDAPCONF
fi
/bin/mv $NSSWITCH.se3sav $NSSWITCH
/bin/mv /var/lib/ldap /var/lib/ldap.old
/bin/mv /var/lib/ldap.se3sav /var/lib/ldap
#mysqladmin drop se3db
#echo "DELETE FROM user WHERE User = 'se3db_admin'"|mysql mysql -u root -p
#echo "DELETE FROM db WHERE User = 'se3db_admin'"|mysql mysql -u root -p

$INITDNSCD start
$INITDSLAPD start
$INITDSAMBA start
