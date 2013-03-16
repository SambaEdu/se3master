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


if [ -e "/var/www/se3" ] 
then
  echo "Se3 detected"
  DB="se3db"
  SRV="se3"
else
  echo "LCS detected"
  DB="lcs_db"
  SRV="lcs"
USERDEL=
fi

# On cree le compte avec un pass aleatoire
getent passwd assist >/dev/null && ADM=1
PASS=`date | md5sum | cut -c 3-9`

if [ "$ADM" = "1" ]
then
echo "Le compte assist existe déjà"
echo "Vérifiez que le compte n'est pas un compte utilisateur comme par ex thibault assis"
echo "vous pouver ensuite changer le mot de passe avec userChangePwd.pl assist PASS"
exit 1
fi

UIDPOLICY=`echo "SELECT value FROM params WHERE name='uidPolicy'" | mysql -h localhost $DB -N`
echo "UPDATE params SET value='4' WHERE name='uidPolicy'" | mysql -h localhost $DB
/usr/share/$SRV/sbin/userAdd.pl t assis $PASS 00000000 M Administratifs
echo "UPDATE params SET value=\"$UIDPOLICY\" WHERE name='uidPolicy'" | mysql -h localhost $DB
	
echo "compte administrateur temporaire cree"
echo "login: assist"
echo "passw: $PASS"
echo "ce compte expirera dans une heure"


# Le compte expirera dans une heure
echo  "/usr/share/$SRV/sbin/userDel.pl assist" | at now+1 hour

# Mise en place des droits pour le compte assist

peopleRdn=`mysql $DB -B -N -e "select value from params where name='peopleRdn'"`
ldap_base_dn=`mysql $DB -B -N -e "select value from params where name='ldap_base_dn'"`
rightsRdn=`mysql $DB -B -N -e "select value from params where name='rightsRdn'"`
cDn="uid=assist,$peopleRdn,$ldap_base_dn"

for right in $(ldapsearch -xLLL cn -b ou=rights,$ldap_base_dn| grep cn: | cut -d" " -f2)
do
pDn="cn=$right,$rightsRdn,$ldap_base_dn" && /usr/share/$SRV/sbin/groupAddEntry.pl "$cDn" "$pDn"
done

if [ -e "/var/cache/se3_install/wpkg-install.sh" ] 
then
echo "Mise en place des droits sur interface wpkg"
/var/cache/se3_install/wpkg-install.sh >/dev/null
fi

echo "Ajout de assist au group admins pour cnx TS sur les clients windows"
/usr/share/$SRV/sbin/groupAddUser.pl assist admins

if [ -e "/home/assist" ]; then
      if [ "$SRV" == "lcs" ]; then
      ### On adapte les droit pour LCS 2
      chown root\:lcs-users /home/assist
      chmod 750 /home/assist
      else
      chown -R assist\:lcs-users /home/assist
      [ -e /home/profile/assist ]  chown -R assist\:lcs-users /home/profile/assist
      fi
fi














