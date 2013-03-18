#!/bin/bash

#
## $Id$ ##
#
##### script permettant de modifier /etc/fstab afin d'activer les quotas #####
# franck molle 13/04/2005 version 0.1 finale

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script permettant de modifier fstab afin d'activer les quotas"
	echo "Usage : aucune option"
	exit
fi	

#Couleurs
COLTITRE="\033[1;35m"	# Rose
COLPARTIE="\033[1;34m"	# Bleu

COLTXT="\033[0;37m"	# Gris
COLCHOIX="\033[1;33m"	# Jaune
COLDEFAUT="\033[0;33m"	# Brun-jaune
COLSAISIE="\033[1;32m"	# Vert

COLCMD="\033[1;37m"	# Blanc

COLERREUR="\033[1;31m"	# Rouge
COLINFO="\033[0;36m"	# Cyan

echo -e "$COLTITRE"
echo -e "Installation et activation des quotas"
echo -e "$COLTXT"

LADATE=$(date +%D_%Hh%M | sed -e "s!/!_!g")
FSTAB_TMP="/tmp/fstab"
FSTAB_ORI="/etc/fstab"
echo "" > $FSTAB_TMP

echo -e "$COLTXT"
echo "Modification de fstab si nécessaire..."
echo -e "$COLCMD\c"
while read LIGNE
do
	XFS_DETECT=$(echo $LIGNE | grep xfs)
	if [ "$XFS_DETECT" != "" ]; then
		QUOTAS_OK=$(echo "$LIGNE" | grep "defaults,quota")
		if [ -z "$QUOTAS_OK" ]; then
        		echo "$LIGNE" | sed -e "s/defaults/defaults,quota/" >>  $FSTAB_TMP
		else
			echo "$LIGNE" >> $FSTAB_TMP
		fi

	else
		echo "$LIGNE" >> $FSTAB_TMP
	fi
done < $FSTAB_ORI
mv $FSTAB_ORI ${FSTAB_ORI}.sauve_$LADATE
mv $FSTAB_TMP $FSTAB_ORI

FSTAB_SAUV=${FSTAB_ORI}.sauve_$LADATE
LADATE=$(date +%D_%Hh%M | sed -e "s!/!_!g")
FICHIERLOG=/root/init_quota.log 
/etc/init.d/samba stop
sleep 2
umount /home
umount /var/se3
mount -a
if [ $? != 0 ]; then
echo "** ERREUR ** lors du remontage des partitions XFS, restauration du fichier de sauvegarde"  | tee -a $FICHIERLOG 
mv  $FSTAB_SAUV /etc/fstab
mount -a
else
echo "** Mise en place des quotas sur le serveur avec succes**"  | tee -a $FICHIERLOG 
fi
/etc/init.d/samba start
