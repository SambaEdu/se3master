#!/bin/bash


## $Id$ ##


# supprimer complétement un sous-rép du rép classe des élèves au nom du devoir 

rep=$1

if [ -d $rep ]
then
 rm -r $rep
 [ ! -d $rep ] && echo 1
fi

