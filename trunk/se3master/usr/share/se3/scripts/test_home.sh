#!/bin/bash
# tester l'existence du cr�er un sous-r�p du home du prof pour y recueillir les devoirs

login=$1
[ -d "/home/$login" ] && echo 1


