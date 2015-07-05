#!/bin/bash


## $Id$ ##


# Appliquer des acls version test

effacer=$1
types=$2
nom=$3
lecture=$4
ecriture=$5
execution=$6
repertoire=$7
defaut=$8
propagation=$9

if [ $nom = "x" ]
then
nom=""
fi

if [ $defaut = "non" ]
then
setfacl $propagation $effacer $types:$nom:$lecture$ecriture$execution "$repertoire"
fi

if [ $defaut = "oui" ]
then
setfacl $propagation $effacer d:$types:$nom:$lecture$ecriture$execution "$repertoire"
fi

if [ $effacer = "eff" ]
then
setfacl $propagation -x $types:$nom "$repertoire"
fi

if [ $effacer = "effd" ]
then
setfacl $propagation  -x d:$types:$nom "$repertoire"
fi
