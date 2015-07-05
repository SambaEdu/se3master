#!/bin/bash


## $Id$ ##



# modifier les noms des répertoires des devoirs des élèves 
# de rep_devoir à rep_devoir_nv

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

