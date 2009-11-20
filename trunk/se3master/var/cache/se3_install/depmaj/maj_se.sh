#!/bin/bash

# Olivier LECLUSE - 1 septembre 2005
# Mise a jour automatique de samba-edu3 - deploiement #ETAB# Caen

if [ ! -d /tmp/maj ]; then
  mkdir /tmp/maj
else
  rm /tmp/maj/* -r 2&>/dev/null
fi
cd /tmp/maj

typeset -i n=`echo "SELECT value FROM params WHERE name='majdepnbr';"|mysql se3db -N`
while true
do
fich=maj#ETAB#-$n
echo "téléchargement de $fich"
wget -qc ftp://ftp.crdp.ac-caen.fr/pub/linux/college/maj/$fich.tar.gz
wget -qc ftp://wawadeb.crdp.ac-caen.fr/pub/college/maj/$fich.md5
if [ -e $fich.tar.gz ]; then
  echo "téléchargement de $fich terminé"
  MD5=`cat $fich.md5`
  MD51=`md5sum $fich.tar.gz`
  if [ "$MD5" = "$MD51" ]; then
    tar -zxf $fich.tar.gz
    ./maj.sh
    echo "mise a jour #$n terminée"
  else
    echo "Erreur de CRC sur $fich"
    exit 1
  fi
  let n+=1
else
  rm -r /tmp/maj/* 2&>/dev/null
  mysql -D se3db -e "UPDATE params SET value=$n WHERE name='majdepnbr';"
  echo "Opération de mise a jour terminée"
  exit 0
fi
done
