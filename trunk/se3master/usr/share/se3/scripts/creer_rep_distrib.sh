#!/bin/bash


## $Id$ ## 


# créer un sous-rép du rép classe des élèves au nom du devoir pour y recueillir les devoirs
# appel :  $cr=exec("/usr/bin/sudo /usr/share/se3/scripts/creer_rep_distrib.sh $login $id_eleve \"$rep\"");


login=$1
uid=$2
rep=$3

if [ ! -d "$rep" ]
then
 mkdir "$rep"
 chown admin:nogroup "$rep"
 chmod  700  "$rep"
 setfacl -m u:$login:rwx "$rep"
 setfacl -m u:$uid:rwx "$rep"
 setfacl -m d:u:$login:rwx "$rep"
 setfacl -m d:u:$uid:rwx "$rep"
 setfacl -m m::rwx "$rep"

 # pour un retour
 [ -d "$rep" ] && echo 1
fi

