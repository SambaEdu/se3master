#!/bin/bash

## $Id$ ##


# copier un devoir d'élève dans un rép du home de son prof
# paramétres : $login $id_dev $date_distrib $uid $classe


login=$1
id_devoir=$2
nom_devoir=$3
uid=$4
classe=$5

cd "/var/se3/Classes/$classe/$uid/$id_devoir"
# rep="/home/$login/Devoirs/$devoir-$dat"
rep="/home/$login/Devoirs/$id_devoir"
for fich in *
do
   # éliminer la dernière extension éventuelle des noms de fichiers $i
   nom=${fich%.*}
#   if [ $nom = $devoir ]
   nom_maj=$(echo $nom | tr 'a-z' 'A-Z') 
   nom_devoir_maj=$(echo $nom_devoir | tr 'a-z' 'A-Z')
#   if [ $nom_maj = "DEVOIR" ]
   if [ $nom_maj = $nom_devoir_maj ]
   then
     ext=${fich#$nom}
     dest="$rep/$uid$ext"
     cp  $fich $dest
   # mettre les droits de propriété
     chown $login $dest
     chmod 700 $dest
     [ -f $dest ] && echo 1
   fi
done
