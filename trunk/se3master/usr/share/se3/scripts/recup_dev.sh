#!/bin/bash


## $Id$ ##


# copier le fichier pour recuperation par le web

login=$1
fich="/home/$login/devoirs.txt"

if [ -f  $fich ]
then
 cp $fich /tmp/devoirs.txt
 chown www-se3 /tmp/devoirs.txt
 chmod 600  /tmp/devoirs.txt
fi
