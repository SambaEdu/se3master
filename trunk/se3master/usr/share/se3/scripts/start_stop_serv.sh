#!/bin/bash

##### Stop ou reboot le serveur #####
#
## $Id$ ##


if [ "$1" == "--help" -o "$1" == "-h" ]
then
	echo "Script d'arr�t ou de reboot du serveur"
	echo "Pas d'option"
	echo "--help cette aide"
	
	exit
fi

if [ "$1" == "stop" ]
then
	logger -t "Stop serveur" "Arr�t du serveur demand�"
	/sbin/halt
fi

if [ "$1" == "restart" ]
then

	logger -t "Restart serveur" "Reboot du serveur demand�"
	/sbin/reboot
fi	

