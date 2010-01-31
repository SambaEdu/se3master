#!/bin/bash
## $Id$ ##
#
##### Permet de positionner les droits pour backuppc #####
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script permettant de positionner les droits pour backuppc."
	
	echo "Usage : pas d'option"
	exit
fi	

bck_user="backuppc"

chown -R www-se3.backuppc /usr/share/backuppc
chown -R $bck_user.www-data /etc/backuppc
chmod -R 770 /etc/backuppc
chown $bck_user.www-data /etc/SeConfig.ph
chmod 640 /etc/SeConfig.ph
chown $bck_user /usr/share/backuppc/cgi-bin/index.cgi
chmod u+s /usr/share/backuppc/cgi-bin/index.cgi
chown -R $bck_user /var/run/backuppc
getfacl /var/lib/backuppc 2>/dev/null|grep owner|grep $bck_user||chown -R $bck_user /var/lib/backuppc
