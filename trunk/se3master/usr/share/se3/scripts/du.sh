#!/bin/bash

#
## $Id$ ##
#


##### liste en HTML la taille des sous-répertoires, rangés par ordre de taille décroissante #####
#
# Etat ement d'un utilisateur

# Olivier LECLUSE 03 10 1999

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Liste en html les sous-répertoires, rangés pas ordre de taille décroissante."
	echo "Usage : du.sh /home/toto"
	exit
fi	

chemin_lock="/var/lock"
# Nom du fichier de lock:
fich_lock="$chemin_lock/du.lck"


/usr/bin/find $chemin_lock -name du.lock -cmin +15 -delete



if [  -e "$fich_lock" ]; then
	echo "Instance en cours.....Patientez 15 mn qu'elle se termine"
	exit 1
fi

touch "$fich_lock"

if [ ! -e $1 ]; then
  echo "Le répertoire passé en argument n'existe pas!"
  exit 1
else
  WREP=$1
  echo "
  Liste des sous-répertoires directs, rangés par ordre de taille décroissante.
  <BR><BR>
  <TABLE ALIGN=\"CENTER\" BORDER=\"1\">
  <TR><TD><STRONG>Dossier</STRONG></TD>
  <TD ALIGN=\"center\"><STRONG>Taille (Mo)</STRONG></TD>"
  
  find $WREP -maxdepth 1 -type d -print | xargs du -sk | sort -rn | while true
  do
          read ligne
          if [ "$ligne" = "" ]; then
                  break
          fi
          set -- $ligne
          echo "<TR><TD>"
          echo $2; echo "</TD><TD ALIGN='CENTER'>"
          let occ=$1/1024
          echo $occ; echo "</TD></TR>"
  done
  
  echo "</TABLE><BR>"
fi

rm -f "$fich_lock"