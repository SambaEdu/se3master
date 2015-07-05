#!/bin/bash

## $Id$ ##


# créer un sous-rép du home du prof pour y recueillir les devoirs

login=$1
devoir=$2

if [ ! -d "/home/$login/Devoirs/$devoir" ]
then
 mkdir -p "/home/$login/Devoirs/$devoir"
 chown $login "/home/$login/Devoirs/$devoir"
 chmod 700 "/home/$login/Devoirs/$devoir"
 # le mask n'est pas correct pour le dossier $devoir : l'utilisateur ne peut pas le supprimer
 setfacl -m m:rwx "/home/$login/Devoirs"
 setfacl -m m:rwx "/home/$login/Devoirs/$devoir"
 # pour un retour
 [ -d "/home/$login/Devoirs/$devoir" ] && echo 1
fi

