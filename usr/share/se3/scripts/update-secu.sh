#!/bin/bash

## $Id$ ##
#
##### Retourne si une maj de sécurité Débian est nécessaire #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Retourne si une maj de sécurité est à faire"
	echo "Usage : aucune option"
	exit
fi

# Remplacé dans la crontab
# apt-get update

MAJ=`apt-get -s dist-upgrade 2>/dev/null | grep 'Debian-Security'`
if [ "$?" = "0" ]
then
	echo "maj a faire"
	echo "0"
	exit 0
else 
	echo "Aucune maj à faire"
	echo "1"
	exit 1
fi	

