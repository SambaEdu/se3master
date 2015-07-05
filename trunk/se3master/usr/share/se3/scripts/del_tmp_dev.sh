#!/bin/bash


## $Id$  ##


# supprimer les 2 fichier temporaires

tmp="/tmp/devoirs.txt"
tmp1="/tmp/devoirs1.txt"

if [ -f  $tmp ]
then
 rm $tmp
fi
if [ -f  $tmp1 ]
then
 rm $tmp1
fi
