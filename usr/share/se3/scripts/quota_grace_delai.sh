#!/bin/bash
# Auteur: Olivier Lacroix, version 0.2



## $Id$ ##


#
##### Script permettant le reglage du delai de grace sur les partitions où les quotas sont activés #####
#

if [ $# -ne 2 -o "$1" = "--help" -o "$1" = "-h" ]; then
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

if [ $? -ne "0" ]; then
echo "ERREUR DE SYNTAXE:"
echo
echo "Ce script n'admet, comme 1er argument, qu'un nombre de jours (entier positif)!"
echo
exit 1
fi

if [ ! $2 = "/home" -a ! $2 = "/var/se3" ] ; then
echo "ERREUR DE SYNTAXE:"
echo
echo "Ce script n'admet, comme 2eme argument, que:"
echo "/home ou /var/se3"
echo
exit 1
fi

#teste l'install du paquet quota
if [ ! -f /usr/sbin/setquota ]; then
ERREUR "Le paquet quota n'est pas installé.\nEffectuez:\n\tapt-get update\n\tapt-get install quota"
exit 1
fi

delai=$[3600*24*$1]
fstype=$(grep  $2 /etc/mtab)
if $(echo $fstype | grep -q xfs); then
        /usr/sbin/setquota -F xfs -t $delai 0 $2
	echo "DELAI DE $1 JOURS FIXE AVEC SUCCES SUR $2."

elif $(echo $fstype | grep -q zfs); then
#        zvol=$(echo $fstype | awk '{ print $1 }')
#        newquota=$(( $2 * 1049 ))
#        /sbin/zfs set userquota@$1=$newquota $zvol
	echo "on ne fixe pas de delai sur $2 en ZFS!" 
else
        /usr/sbin/setquota -t $delai 0 $2
	echo "DELAI DE $1 JOURS FIXE AVEC SUCCES SUR $2."
fi


