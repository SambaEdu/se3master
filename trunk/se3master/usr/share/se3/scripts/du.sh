#!/bin/bash

#
## $Id$ ##
#
##### liste en HTML la taille des sous-r�pertoires, rang�s par ordre de taille d�croissante #####
#
# Etat ement d'un utilisateur

# Olivier LECLUSE 03 10 1999

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Liste en html les sous-r�pertoires, rang�s pas ordre de taille d�croissante."
	echo "Usage : du.sh /home/toto"
	exit
fi	

if [ ! -e $1 ]; then
  echo "Le r�pertoire pass� en argument n'existe pas!"
  exit 1
else
  WREP=$1
  echo "
  Liste des sous-r�pertoires directs, rang�s par ordre de taille d�croissante.
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
