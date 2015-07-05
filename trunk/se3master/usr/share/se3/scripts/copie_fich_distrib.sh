#!/bin/bash


## $Id$ ##


# copier un fichier distribué par un prof dans un s-rép de la classe de l'élève
# appel : $ch1 ="/usr/bin/sudo /usr/share/se3/scripts/copie_fich_distrib.sh $login $id_eleve \"$rep\" \"$fichiers[$i]\" ";

login=$1
uid=$2
rep=$3
fichier=$4
equipe=$5

if [ -d "$rep" ]
then
 #cp -a "/tmp/$fichier"  $rep
 #cp -a "/tmp/$login/$fichier"  $rep
 cp -a "/tmp/$login/$fichier"  "$rep"
 fich="$rep/$fichier"
 chown admin:nogroup "$fich"
 chmod 700  "$fich"

 setfacl -m u:$login:rwx "$fich"
 setfacl -m u:$uid:rwx "$fich"
 setfacl -m g:$equipe:rwx "$fich"

 [ -f "$fich" ] && echo 1
fi
