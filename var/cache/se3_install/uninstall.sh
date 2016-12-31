#!/bin/bash

# **********************************************************
# Désinstallation de SambaEdu3 Version 0.1
# 22 Juillet 2002
# Auteur: Olivier LECLUSE
# Ce script est diftribué selon les termes de la licence GPL
# **********************************************************

clear
echo "Ce script détruira toute trace de SambaEdu sauf les données utilisateurs !!"
echo "Pour poursuivre, tapez \"Je suis SUR\""
read rep
if [ ! "$rep" = "Je suis SUR" ]; then
	echo "Abandon de la désinstallation"
exit 0
fi


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


$INITDSAMBA stop
$INITDSLAPD stop


/bin/rm -r /usr/share/se3
/bin/rm $WWWPATH/se3 -r
/bin/rm $CGIPATH/gep.cgi
/bin/rm /etc/SeConfig.ph
/bin/rm /usr/lib/perl5/Se.pm


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
exit 0