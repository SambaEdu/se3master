#!/bin/bash


## $Id$ ##


function cleanpl
{
	for user in /home/*
	do	
		[ -d "$user"/profil/Bureau ] && find "$user"/profil/Bureau -user root -type f -exec rm -f "{}" \;
		[ -d "$user"/profil/Demarrer ] && find "$user"/profil/Demarrer -user root -type f -exec rm -f "{}" \;
	done
}

function cleanpy
{
	for user in /home/*
	do	
		[ -d "$user"/profil/Bureau ] && find "$user"/profil/Bureau -user root -type l -exec rm -f "{}" \;
		[ -d "$user"/profil/Demarrer ] && find "$user"/profil/Demarrer -user root -type l -exec rm -f "{}" \;
	done
}


logon_env="$(cat /etc/se3/logon_env.conf 2>/dev/null)"

if [ "$logon_env" == "logonpl" ]
then
	cleanpy
	rm -rf /home/profiles/*
elif [ "$logon_env" == "logonpy" ]
then
	cleanpl
	for user in /home/*
	do
		[ -d "$user"/profile ] && rm -rf "$user"/profile/*
	done
fi
