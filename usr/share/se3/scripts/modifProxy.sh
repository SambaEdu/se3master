#!/bin/sh

#
## $Id$ ##
#
##### script de modif de /etc/profile afin que la machine passe par un proxy #####
# modestement ecrit par franck molle 07/2004

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Modifie /etc/profile pour ajouter la conf d'un proxy"
	echo "Sans option le proxy est supprimé"
	echo "Usage : modifProxy.sh [adresse_ip:port]"
	exit
fi	

# Si on a deja un proxy
proxy=`cat /etc/profile | grep http_proxy=` 
if [ "$proxy" != "" ]
then 
	perl -pi -e 's/http_proxy=.*\n//' /etc/profile
	perl -pi -e 's/ftp_proxy=.*\n//' /etc/profile
	perl -pi -e 's/.*http_proxy.*\n//' /etc/profile
	perl -pi -e 's/^http_proxy = .*\n//' /etc/wgetrc
fi	
if [ "$1" != "" ]
then
	echo "http_proxy=\"http://$1\"" >> /etc/profile
	echo "ftp_proxy=\"http://$1\"" >> /etc/profile
	echo "export http_proxy ftp_proxy" >> /etc/profile
	echo "http_proxy = http://$1" >> /etc/wgetrc
fi	
