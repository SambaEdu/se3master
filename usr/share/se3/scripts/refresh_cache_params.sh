#!/bin/bash

## $Id$ ##
#
##### actualisation du cache des parametres #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "actualisation du cache des parametres"
	echo "Usage : aucune option"
	exit
fi

/usr/share/se3/includes/config.inc.sh -clpbmsdf 

