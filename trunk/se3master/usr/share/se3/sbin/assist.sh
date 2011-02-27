#!/bin/bash

## $Id$ ##
#
##### Permet de créer un compte d'assistance pour l'interface web pdt 1 heure #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script permettant de créer un compte pour les services d'assistance académique."
	echo "Le compte permet l'accès complet à l'interface web, il est détruit après une heure."
	
	echo "Usage : pas d'option"
	exit
fi	


# On cree le compte avec un pass aleatoire
getent passwd assist >/dev/null && ADM=1
PASS=`date | md5sum | cut -c 3-9`

[ "$ADM" = "1" ] && echo "Le compte assist existe déjà" && exit 1
UIDPOLICY=`echo "SELECT value FROM params WHERE name='uidPolicy'" | mysql -h localhost se3db -N`
echo "UPDATE params SET value='4' WHERE name='uidPolicy'" | mysql -h localhost se3db
/usr/share/se3/sbin/userAdd.pl t assis $PASS 00000000 M Administratifs
echo "UPDATE params SET value=\"$UIDPOLICY\" WHERE name='uidPolicy'" | mysql -h localhost se3db
	
echo "compte administrateur temporaire cree"
echo "login: assist"
echo "passw: $PASS"
echo "ce compte expirera dans une heure"


# Le compte expirera dans une heure
echo  "/usr/share/se3/sbin/userDel.pl assist" | at now+1 hour

# Mise en place des droits pour le compte assist

peopleRdn=`mysql se3db -B -N -e "select value from params where name='peopleRdn'"`
ldap_base_dn=`mysql se3db -B -N -e "select value from params where name='ldap_base_dn'"`
rightsRdn=`mysql se3db -B -N -e "select value from params where name='rightsRdn'"`
cDn="uid=assist,$peopleRdn,$ldap_base_dn"
pDn="cn=se3_is_admin,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=Annu_is_admin,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=computers_is_admin,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=printers_is_admin,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=sovajon_is_admin,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=system_is_admin,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=echange_can_administrate,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=inventaire_can_read,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=annu_can_read,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=maintenance_can_write,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=parc_can_view,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"
pDn="cn=parc_can_manage,$rightsRdn,$ldap_base_dn" && /usr/share/se3/sbin/groupAddEntry.pl "$cDn" "$pDn"




