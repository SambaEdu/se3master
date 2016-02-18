#!/bin/bash

# Script de création du dossier au nom du parc dans /home/template
# Stéphane Boireau - 18/03/2005


## $Id$ ##

if [ ! -z "$1" ]; then
        verif=$(echo "$1" | sed -e "s/[A-Za-z0-9_.]//g" | wc -c | sed -e "s/ //g")
        if [ "$verif" = "1" ]; then
                if [ ! -e "/home/templates/$1" ]; then
                        mkdir -p /home/templates/$1
                        mkdir -p /home/templates/$1/Bureau
                        mkdir -p /home/templates/$1/Demarrer/Programmes
                        echo "rem Script de login du parc $1" > /home/templates/$1/logon.bat
                        echo "rem Partie commune a tous les clients." >> /home/templates/$1/logon.bat
                        vim -c "set ff=dos"  -c ":wq" /home/templates/$1/logon.bat

                        echo "rem Script de login du parc $1" > /home/templates/$1/logon_Win95.bat
                        echo "rem Partie specifique aux clients win9x" >> /home/templates/$1/logon_Win95.bat
                        vim -c "set ff=dos"  -c ":wq" /home/templates/$1/logon_Win95.bat

                        echo "rem Script de login du parc $1" > /home/templates/$1/logon_Win2K.bat
                        echo "rem Partie specifique aux clients win2k/xp" >> /home/templates/$1/logon_Win2K.bat
                        vim -c "set ff=dos"  -c ":wq" /home/templates/$1/logon_Win2K.bat
                fi
        fi
fi
