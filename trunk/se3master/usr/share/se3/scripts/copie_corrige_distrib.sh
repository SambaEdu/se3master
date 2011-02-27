#!/bin/bash

# $Id$ #

# copier un corrig� global dans les s-r�p de devoir de l'�l�ve
# appel : $cr=exec("/usr/bin/sudo /usr/share/se3/scripts/copie_corrige_distrib.sh $uid_eleve \"$rep\" \"$nom_fichier\" $login") ;  

uid=$1
rep=$2
fichier=$3
# Ajout:
prof=$4

if [ -d "$rep" ]
then
 nom=${fichier%.*}
 ext=${fichier#$nom}
 dest="$rep/CORRIGE$ext"
 #cp -a "/tmp/$fichier"  $dest
 #cp -a "/tmp/$prof/$fichier"  $dest
 
 cp -a "/tmp/$prof/$fichier" "$dest"
 chown admin:nogroup "$dest"
 chmod 700  "$dest"
 setfacl -m u:$uid:rwx "$dest"
 setfacl -m u:$prof:rwx "$dest"
 
# setfacl -m m::rwx "$dest"
 
 [ -f "$dest" ] && echo 1
fi
