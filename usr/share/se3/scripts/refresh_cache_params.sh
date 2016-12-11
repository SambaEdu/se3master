#!/bin/bash

## $Id$ ##
#
##### actualisation du cache des parametres #####
# 11-2016 Ajout test sur mysql

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "actualisation du cache des parametres"
	echo "Usage : aucune option"
	exit
fi

mysql_ok=$(/usr/share/se3/includes/config.inc.sh -clpbmsd 2>&1 | grep ERROR)
if [ -z "$mysql_ok" ]; then
	/usr/share/se3/includes/config.inc.sh -clpbmsdf 
else
	if [ ! -e "dead.letter" ]; then
	alerte="/root/alerte_mysql"
	date_alerte=0
		if [ -e "$alerte" ]; then
			date_alerte="$(stat -c %Y alerte_mysql)"
			now="$(date +%s)"
			delta=$(($now-$date_alerte))
			if [ "$delta" -gt "86000" ]; then
				echo "Alerte : Mysql semble avoir un problème"
				touch "/root/alerte_mysql"
			fi
		else
			touch "/root/alerte_mysql"
			echo "Alerte : Mysql semble avoir un problème"
		fi
	fi
fi

exit 0
