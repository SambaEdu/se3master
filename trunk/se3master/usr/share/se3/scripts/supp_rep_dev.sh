#!/bin/bash
# supprimer compl�tement un sous-r�p du r�p classe des �l�ves au nom du devoir 

rep=$1

if [ -d $rep ]
then
 rm -r $rep
 [ ! -d $rep ] && echo 1
fi

