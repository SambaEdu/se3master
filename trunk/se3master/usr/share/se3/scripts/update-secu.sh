#!/bin/bash

## $Id$ ##
#
##### Retourne si une maj de s�curit� D�bian est n�cessaire #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Retourne si une maj de s�curit� est � faire"
	echo "Usage : aucune option"
	exit
fi

# Remplac� dans la crontab
# apt-get update

MAJ=`apt-get -s dist-upgrade 2>/dev/null | grep 'Debian-Security'`
if [ "$?" = "0" ]
then
	echo "maj a faire"
	echo "0"
	exit 0
else 
	echo "Aucune maj � faire"
	echo "1"
	exit 1
fi	

