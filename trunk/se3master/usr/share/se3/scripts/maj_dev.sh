#!/bin/bash
# mettre l'etat d'un devoir � etat = 0 (r�cup�r�), � faire r�cup partielle)
login=$1
fich=$2

dest="/home/$login/devoirs.txt"
if [ -f  $fich ]
then
 cp $fich  $dest
 chown $login  $dest
 chmod 600  $dest
fi

