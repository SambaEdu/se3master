#!/bin/bash

## $Id$ ##
#
##### Retourne si une maj se3 est nécessaire #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Retourne si une maj de se3 est à faire"
	echo "Usage : aucune option"
	exit
fi

MAJ=`apt-get -s dist-upgrade 2>/dev/null | grep 'se3-'`
if [ "$?" = "0" ]
then
    echo "0"
    exit 0
else
    echo "1"
    exit 1
fi

