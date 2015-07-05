#!/bin/bash


## $Id$ ##


# Script de création du dossier au nom du parc dans /home/template
# Stéphane Boireau - 06/04/2005
#Avec le précieux coup de main de 'out' pour PERL.

if [ ! -z "$1" ]; then
	#verif=$(echo "$1" | sed -e "s/[A-Za-z0-9_.]//g" | wc -c | sed -e "s/ //g")
	verif=$((`echo "$1" | tr -d "[A-Za-z0-9_.]" | wc -c`))
	if [ "$verif" = "1" ]; then
		if [ ! -e "/home/templates/$1" ]; then

			if [ -z "$2" ]; then
				type="parc"
			else
				type="groupe"
			fi

			ladate=$(date +%Y%m%d%H%M%S)

			mkdir -p /home/templates/$1
			mkdir -p /home/templates/$1/Bureau
			mkdir -p /home/templates/$1/Demarrer/Programmes
			echo -e "rem Script de login du $type $1" > /home/templates/$1/logon.bat.${ladate}
			echo -e "rem Partie commune a tous les clients." >> /home/templates/$1/logon.bat.${ladate}
			cat /home/templates/$1/logon.bat.${ladate} | perl -pe 's/\n/\r\n/' > /home/templates/$1/logon.bat
			rm -f /home/templates/$1/logon.bat.${ladate}

			echo -e "rem Script de login du $type $1" > /home/templates/$1/logon_Win95.bat.${ladate}
			echo -e "rem Partie specifique aux clients win9x" >> /home/templates/$1/logon_Win95.bat.${ladate}
			cat /home/templates/$1/logon_Win95.bat.${ladate} | perl -pe 's/\n/\r\n/' > /home/templates/$1/logon_Win95.bat
			rm -f /home/templates/$1/logon_Win95.bat.${ladate}
			
			echo "rem Script de login du $type $1" > /home/templates/$1/logon_Win2K.bat.${ladate}
			echo "rem Partie specifique aux clients win2k" >> /home/templates/$1/logon_Win2K.bat.${ladate}
			cat /home/templates/$1/logon_Win2K.bat.${ladate} | perl -pe 's/\n/\r\n/' > /home/templates/$1/logon_Win2K.bat
			rm -f /home/templates/$1/logon_Win2K.bat.${ladate}

			echo "rem Script de login du $type $1" > /home/templates/$1/logon_WinXP.bat.${ladate}
			echo "rem Partie specifique aux clients winxp" >> /home/templates/$1/logon_WinXP.bat.${ladate}
			cat /home/templates/$1/logon_WinXP.bat.${ladate} | perl -pe 's/\n/\r\n/' > /home/templates/$1/logon_WinXP.bat
			rm -f /home/templates/$1/logon_WinXP.bat.${ladate}
		fi
	fi
fi
