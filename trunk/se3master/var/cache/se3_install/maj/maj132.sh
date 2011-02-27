#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj132"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)


userdel keyser 2>/dev/null
groupdel keyser 2>/dev/null

quotas_actifs=$(grep "defaults,quota" /etc/fstab)

if [ -z "$quotas_actifs" ]; then

  LADATE=$(date +%D_%Hh%M | sed -e "s!/!_!g")
  FSTAB_TMP="/tmp/fstab"
  FSTAB_ORI="/etc/fstab"
  echo "" > $FSTAB_TMP

  echo "Modification de fstab pour activation quotas..."
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
  echo "Modification de fstab pour activation des quotas OK"
  echo "Le changement sera effectif au prochain reboot du serveur"
fi


echo "Mise a jour 132:
- Activation quotas si besoin 
- supresssion groupe keyser, utilisateur keyser si existant" >> $HISTORIQUE_MAJ

exit 0
