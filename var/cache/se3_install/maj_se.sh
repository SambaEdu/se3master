#!/bin/bash

## $Id$ ## 

# **********************************************************
# Mise à jour de SambaEdu v 0.5
# Auteur: Olivier LECLUSE
# Colorisation: 18/05/2005
# Ce script est distribué selon les termes de la licence GPL
# **********************************************************

CONT=$1

#Couleurs - désactivation des couleurs - keyser car posant pb 
# lors de l'affichage ds une page web

. /usr/share/se3/includes/config.inc.sh -clmsb
. /usr/share/se3/includes/functions.inc.sh

#date et heure
LADATE=$(date +%d-%m-%Y)

# path fichier de logs
LOG_DIR="/var/log/se3"

clear

echo "*************************"
echo "* SCRIPT DE MISE A JOUR *"
echo "*     DE SAMBAEDU3      *"
echo "*************************"

echo ""
cat /etc/passwd | grep www-se3 > /dev/null || ADDWWWSE3="1"

if [ "$ADDWWWSE3" = "1" ]; then
	useradd -d /var/remote_adm -s /bin/bash
	sleep 5
fi

LDAPIP="$ldap_server"
if [ -z "$LDAPIP" ]; then
	echo "Impossible d'accéder aux paramètres SambaEdu."
	exit 1
fi

MAJNBR="$majnbr"
MAJNBRORI="$MAJNBR"

if [ -z "$MAJNBR" ]; then
	MAJNBR=0
fi

if [ "$(lsb_release -sc)" = "wheezy" ]; then
	[ $MAJNBR -le 160 ] && MAJNBR=160
fi

if [ $MAJNBR -le 119 ]; then
	NEXT_MINOR_MAJ=$MAJNBR
else
	NEXT_MINOR_MAJ=$(( $MAJNBR+1 ))
fi
NEXT_MAJOR_MAJ=$(( (($MAJNBR /10) * 10) + 10 ))


if [ ! -e maj/maj$NEXT_MINOR_MAJ.sh ] && [ ! -e maj/maj$NEXT_MAJOR_MAJ.sh ] ; then
	
	if [ ! "$CONT" = "C" ]; then
		echo "Pas de script de maj a lancer pour cette fois...."
		# on finit par l'actualisation du cache des parametres : 
		/usr/share/se3/includes/config.inc.sh -clpbmsdf 
		# ya de la maj perso dans l air
		if [ -e "/root/maj-perso.sh" ]; then 
			/root/maj-perso.sh
		fi
		exit 0
	fi
else


	# Lancement des scripts de Mise à jour
	while [ -e maj/maj$NEXT_MINOR_MAJ.sh  ] || [ -e maj/maj$NEXT_MAJOR_MAJ.sh ]; do
		if [ -e maj/maj$NEXT_MINOR_MAJ.sh ]; then
			MAJNBR=$NEXT_MINOR_MAJ
  		elif [ -e maj/maj$NEXT_MAJOR_MAJ.sh ]; then
			MAJNBR=$NEXT_MAJOR_MAJ
  		fi

  		# Application du script de maj
  		echo "Application du script Maj$MAJNBR le $LADATE" | tee -a $LOG_DIR/log_maj$MAJNBR
  		. maj/maj$MAJNBR.sh | tee -a $LOG_DIR/log_maj$MAJNBR
  		NEXT_MINOR_MAJ=$(( $MAJNBR+1 ))
  		NEXT_MAJOR_MAJ=$(( (($MAJNBR /10) * 10) + 10 ))
	done

fi

VERSION=$(cat version)
CHANGEMYSQL version "$VERSION"
CHANGEMYSQL majnbr "$MAJNBR"
echo "Mise a jour vers la version $VERSION achevee."

# Mise a jour des infos statistiques de version
if [ "$registred" = "1" ]; then
	wget http://wawadeb.crdp.ac-caen.fr/majse3/regmaj.php?old=$MAJNBRORI
	wget http://wawadeb.crdp.ac-caen.fr/majse3/regmaj.php?newv=$MAJNBR
	/bin/rm regmaj.php*
fi
echo ""

# on finit par l'actualisation du cache des parametres : 
/usr/share/se3/includes/config.inc.sh -clpbmsdf 

# ya de la maj perso dans l air
if [ -e "/root/maj-perso.sh" ]; then 
	/root/maj-perso.sh
fi
exit 0