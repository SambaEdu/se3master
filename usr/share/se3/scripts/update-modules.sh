#!/bin/bash

## $Id$ ##
#
##### Retourne si une maj se3 est n&cessaire #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Retourne si une maj de se3 est à faire"
	echo "Usage : aucune option"
	exit
fi

apt-get -s install se3-domain 2>/dev/null | grep 'Inst' && MAJ=0
apt-get -s dist-upgrade 2>/dev/null | grep 'se3' && MAJ=0
if [ "$MAJ" = "0" ]
then
    echo "0"
    exit 0
else
    echo "1"
    exit 1
fi

