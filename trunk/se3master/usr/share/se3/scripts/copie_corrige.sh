#!/bin/bash


## $Id$ ##


# copier un devoir d'élève dans un rép du home de son prof
# paramètres : $login $id_dev $date_distrib $uid $classe
# appel :    $ch ="/usr/bin/sudo  /usr/share/se3/scripts/copie_corrige.sh $login \"$id_devoir\" $uid_eleve $cla ";

login=$1
devoir=$2
# dat=$3
uid=$3
classe=$4

rep="/var/se3/Classes/$classe/$uid/$devoir"
cd "/home/$login/Devoirs/$devoir"
# devoir_maj=$(echo $devoir | tr 'a-z' 'A-Z') 

for fich in *
do
  nom=${fich%.*}
  if [ $nom = $uid ]
   then
     ext=${fich#$nom}
     dest="$rep/DEVOIR-CORRIGE$ext"
     cp -a  "$fich" "$dest"
     chown admin:nogroup "$dest"  
     chmod 700 "$dest"  
     setfacl -m u:$uid:rwx "$dest"
     setfacl -m u:$login:rwx "$dest"
     

     [ -f $dest ] && echo 1
   fi
done
