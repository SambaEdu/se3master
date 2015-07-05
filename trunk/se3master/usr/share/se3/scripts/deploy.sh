#!/bin/bash


## $Id$ ##


# Deploie des fichiers dans les home

nom=$1
repertoire=$2
a=$3
file=$4

if [ $repertoire = "x" ]
then
	repertoire=""
fi


if [ ! -e "/home/$nom$repertoire/$file" ]
then
	cp -a "/var/se3/Docs/deploy/$file" "/home/$nom$repertoire"
	chown -R $nom:admins "/home/$nom$repertoire/$file"
elif test $a -eq 1
then
	cp -a "/var/se3/Docs/deploy/$file" /home/$nom$repertoire 
	chown -R $nom:admins "/home/$nom$repertoire/$file"
fi


