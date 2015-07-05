#!/bin/bash


## $Id$ ##


# mettre l'etat d'un devoir à etat = 0 (récupéré), à faire récup partielle)


login=$1
fich=$2

dest="/home/$login/devoirs.txt"
if [ -f  $fich ]
then
 cp $fich  $dest
 chown $login  $dest
 chmod 600  $dest
fi

