#!/bin/bash
# 
# SambaEdu3


### $Id$ ###


#
# Permet de copier les fichiers logon.bat ou logonW95.bat ..etc à partir de l'interface inventaire pour activer l'inventaire

if [ "$1" != "" ] && [ "$2" != "" ] 
then
	# test la présence de $2 dans /tmp
	if [ -f /tmp/$2 ]
	then
		cp /home/templates/$1/$2 /home/templates/$1/$2.old
		cp /tmp/$2 /home/templates/$1/$2
		chown admin.root /home/templates/$1/$2
		chmod 774 /home/templates/$1/$2
#		rm -f /tmp/$2
	else 
		echo "Le fichier $2 n'existe pas dans /tmp"
	fi	
else
	echo "Usage : copylogon.sh template fichier"
fi	
