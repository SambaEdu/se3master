#!/bin/bash

## $Id: refresh_cache_params.sh 5300 2010-02-25 00:09:51Z keyser $ ##
#
##### actualisation du cache des parametres #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "actualisation du cache des parametres"
	echo "Usage : aucune option"
	exit
fi

/usr/share/se3/includes/config.inc.sh -clpbmsdf 

