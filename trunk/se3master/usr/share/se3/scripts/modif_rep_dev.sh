#!/bin/bash
# modifier les noms des r�pertoires des devoirs des �l�ves 
# de rep_devoir � rep_devoir_nv
## $Id$ ##

chemin=$1
rep_devoir=$2
rep_devoir_nv=$3

if [ -d "$chemin/$rep_devoir" ]
then
 mv "$chemin/$rep_devoir"  "$chemin/$rep_devoir_nv"
 # chown $login "$chemin/$rep_devoir_nv"
 # chmod 700  "$chemin/$rep_devoir_nv"
 # pour un retour
 [ -d "$chemin/$rep_devoir_nv" ] && echo 1
fi

