#!/bin/bash
# Auteur: Olivier Lacroix, version 0.2

## $Id$ ##
#
##### Script permettant le reglage du delai de grace sur les partitions où les quotas sont activés #####
#

grep xfs /etc/fstab >/dev/null
if [ "$?" == "0" ]
then
        SET_QUOTA="/usr/sbin/setquota -F xfs"
else
        SET_QUOTA="/usr/sbin/setquota"
fi

if [ $# -ne 2 -o "$1" = "--help" -o "$1" = "-h"]; then
#echo "Le nombre d'arguments du script est incorrect!"
echo 
echo "Passer en arguments dans l'ordre :"
echo "- le delai de grace (en jours) au dela duquel le quota soft devient hard"
echo "- la partition sur laquelle on applique le quota"
echo
echo "Exemple:"
echo "\"quota_grace_delai.sh 7 /home\" fixe un delai de grace de 7 jours sur /home"
echo 
exit 1
fi

# teste pour verifier si $1 est bien un entier positif
test "$1" -gt 0 -o "$1" -eq 0 2>/dev/null
# Un entier positif est soit égal à 0 soit plus grand que 0.

if [ $? -ne "0" ] ; then
echo "ERREUR DE SYNTAXE:"
echo
echo "Ce script n'admet, comme 1er argument, qu'un nombre de jours (entier positif)!"
echo
exit 1
fi

if [ ! $2 == "/home" -a ! $2 == "/var/se3" ] ; then
echo "ERREUR DE SYNTAXE:"
echo
echo "Ce script n'admet, comme 2eme argument, que:"
echo "/home ou /var/se3"
echo
exit 1
fi

#teste l'install du paquet quota
if [ -ne /usr/sbin/setquota ]; then
ERREUR "Le paquet quota n'est pas installé.\nEffectuez:\n\tapt-get update\n\tapt-get install quota"
exit 1
fi

delai=$[3600*24*$1]

$SET_QUOTA -t $delai 0 $2 
echo "DELAI DE $1 JOURS FIXE AVEC SUCCES SUR $2."


