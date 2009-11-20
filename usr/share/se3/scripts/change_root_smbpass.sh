#!/bin/bash
#
## $Id$ ##
#
##### script permettant de changer le pass root smb afin qu'il concorde avec celui des outils w$ (confse3.ini)" #####
#  franck molle 
# mai 2006

if [ ! -z "$1" ]
then
	echo "Script permettant de changer le pass root smb afin qu'il concorde avec celui des outils win$ (confse3.ini)"
	echo "Usage : Aucune option"
	exit
fi	
cp /var/se3/Progs/install/installdll/confse3.ini /root/
dos2unix /root/confse3.ini
NEWPASS="$(cat /root/confse3.ini | grep password_ldap_domain | cut -d"=" -f2)"
echo -e "$NEWPASS\n$NEWPASS"|(/usr/bin/smbpasswd -D 2 -s root)
#echo -e ""${NEWPASS}"\n"${NEWPASS}"" | (/usr/bin/smbpasswd -s root)
rm -f /root/confse3.ini 
exit 0





