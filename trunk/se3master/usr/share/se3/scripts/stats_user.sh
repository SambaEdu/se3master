#!/bin/bash
#Auteur : Denis Bonnenfant

#Amélioration et débuggage: Olivier Lacroix (sur conseils de Stéphane Boireau)
#Version du 11/11/08: corrections de bugs.

## $Id$ ##
#
##### script permettant d'afficher le détail d'coccupation des types de fichier sur /home par user #####
#

AIDE()
{
# echo "Le nombre d'argument est incorrect! Il en faut deux."
 echo
 echo "Exemple d'utilisation de stats_user.sh:"
 echo
 echo "stats_user.sh /home toto     affiche le détail des fichiers (par catégorie) de l'utilisateur toto" 
#Temporaire: pas de possibilité sur /var/se3
 echo
 exit $1
}

[ $# -eq 1 -o $# -eq 0 ] && AIDE 0
[ $# -ne 2 ] && AIDE 1 


part=$1
A=$2
chemin=$part

FICHIERUSER=/tmp/stat_user_$A

# empeche un meme utilisateur de demander son detail plusieurs fois : il pourrait saturer le serveur a coup de find !!
[ -e /tmp/stat_user_$A ] && exit

#total des fichiers dans Docs, Bureau (pour éviter de comptabiliser les profiles)
find $part/$A \! -name '.' -printf "%s\n" > $FICHIERUSER
declare r=0
while read S ; do let r+=$S/1024 ; done < $FICHIERUSER

#total par catégorie
if [ "$part" = "/home" ]
then
  find $part/$A/profil \! -name '.' -printf "%s\n" > $FICHIERUSER
  find $part/$A/profile \! -name '.' -printf "%s\n" >> $FICHIERUSER
  declare c=0
  while read S ; do let c+=$S/1024 ; done < $FICHIERUSER
fi

find $part/$A \( -name *mp3 -o -name *MP3 -o -name *wma \
-o -name *WMA -o -name *wav -o -name *WAV \) -printf "%s\n" > $FICHIERUSER
declare  m=0
while read S ; do let m+=$S/1024 ; done < $FICHIERUSER 

find $part/$A \( -name *mpg -o -name *MPG -o -name *avi \
-o -name *MPEG -o -name *mpeg \
-o -name *AVI -o -name *wmv -o -name *WMV \) -printf "%s\n" > $FICHIERUSER
declare  v=0
while read S ; do let v+=$S/1024 ; done < $FICHIERUSER 

find $part/$A \( -name *jpg -o -name *JPG -o -name *tif \
-o -name *TIF -o -name *gif -o -name *GIF -o -name *bmp -o -name *BMP \
-o -name *odi -o -name *ODI -o -name *odg -o -name *ODG \
\) -printf "%s\n" > $FICHIERUSER
declare i=0
while read S ; do let i+=$S/1024 ; done < $FICHIERUSER 

find $part/$A \( -name *exe -o -name *EXE -o -name *dll \
-o -name *DLL -o -name *scr -o -name *SCR \) -printf "%s\n" > $FICHIERUSER
declare  e=0
while read S ; do let e+=$S/1024 ; done < $FICHIERUSER 

find $part/$A \( -name *zip -o -name *ZIP -o -name *rar \
-o -name *RAR -o -name *cab -o -name *CAB \) -printf "%s\n" > $FICHIERUSER
declare  z=0
while read S ; do let z+=$S/1024 ; done < $FICHIERUSER 

find $part/$A \( -name *doc -o -name *DOC -o -name *xls -o -name *docx -o -name DOCX \
-o -name *XLS -o -name *ppt -o -name *PPT -o -name *pdf -o -name *PDF \
-o -name *odt -o -name *ODT -o -name *ods -o -name *ODS -o -name *odp \
-o -name *ODP -o -name *odc -o -name *ODC -o -name *odb -o -name *ODB \
-o -name *sxw -o -name *SXW \
\) -printf "%s\n" > $FICHIERUSER
declare  w=0
while read S ; do let w+=$S/1024 ; done < $FICHIERUSER 

find $part/$A \( -name *.sld* -o -name *.SLD* -o -name *.CAT* \
-o -name *.cat* -o -name *stl -o -name *STL \) -printf "%s\n" > $FICHIERUSER
declare  t=0
while read S ; do let t+=$S/1024 ; done < $FICHIERUSER 

let references=$m+$v+$i+$e+$z+$w+$t+$c

echo  "TOTAL : $r ko</br>"
echo  "</br>"
if [ ! $m = "0" ]; then
echo  "- Audio (mp3, wav, wma) : $m ko</br>"
fi
if [ ! $v = "0" ]; then
echo  "- Video (avi, mpg) : $v ko</br>"
fi
if [ ! $i = "0" ]; then
echo  "- Images (jpg, tif, png, bmp, odi, odg) : $i ko</br>"
fi
if [ ! $e = "0" ]; then
echo  "- Programmes (exe, dll, scr, vbs) : $e ko</br>"
fi
if [ ! $z = "0" ]; then
echo  "- Archives (zip, rar, cab) : $z ko</br>"
fi
if [ ! $w = "0" ]; then
echo  "- Documents (doc, docx, xls, ppt, pdf, sxw, odt, ods, odp, odc, odb) : $w ko</br>"
fi
if [ ! $t = "0" ]; then
echo  "- CAO (solidworks, catia) : $t ko</br>"
fi
#echo $part
if [ $part = "/home" ]
then
  if [ $[$r-$references] -lt "0" ]
  then #cela signifie que des fichiers référencés (doc, ou autres) ont été comptabilisés deux fois car placés dans profil ou profile (ex: le bureau!) => je les vire du total de fichiers système pour résultat cohérent à l'affichage.
  # BUG REEL: tout fichier dans /home/$A est comptabilisé ds les différents types, le total du profile compte donc en double des fichiers référencés, de plus, le type autre est comptabilisé par différence. C'est sans issue avec cette méthode de décompte!
    let c=$c+$[$r-$references]
  fi
  
  if [ ! $c = "0" ]; then
    echo  "- Fichiers d'environnement système (raccourcis, profil, profile) : $c ko </br>"
  fi
fi
if [ ! $[$r-$references] -lt "0" ]; then
  echo  "- Autres : $[$r-$references] ko</br>"
fi
rm $FICHIERUSER
exit 0

