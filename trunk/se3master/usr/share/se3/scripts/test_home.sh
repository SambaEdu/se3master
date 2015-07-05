#!/bin/bash


## $Id$ ##

# tester l'existence du créer un sous-rép du home du prof pour y recueillir les devoirs

login=$1
[ -d "/home/$login" ] && echo 1


