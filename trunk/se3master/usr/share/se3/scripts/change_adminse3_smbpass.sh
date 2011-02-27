#!/bin/bash
#
## $Id$ ##
#
##### script permettant de changer le pass adminse3 smb afin qu'il concorde avec celui de la bdd #####
# Auteur : franck molle 

if [ ! -z "$1" ]
then
	echo "Script permettant de changer le pass root smb afin qu'il concorde avec celui des outils win$ (confse3.ini)"
	echo "Usage : Aucune option"
	exit
fi	
. /usr/share/se3/includes/config.inc.sh -m

echo -e "$xppass\n$xppass"|(/usr/bin/smbpasswd -s adminse3)
if [  "$?" == "0" ]; then
	echo "Ok !"
else
	echo "Echec !"
fi




