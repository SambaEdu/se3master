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


#date et heure
LADATE=$(date +%d-%m-%Y)

# path fichier de logs
LOG_DIR="/var/log/se3"

clear

echo "*************************"
echo "* SCRIPT DE MISE A JOUR *"
echo "*     DE SAMBAEDU3      *"
echo "*************************"

echo -e "$COLCMD\c "
cat /etc/passwd | grep www-se3 > /dev/null || ADDWWWSE3="1"

if [ "$ADDWWWSE3" = "1" ]; then
	useradd -d /var/remote_adm -s /bin/bash
	sleep 5
fi

DISTRIB="DEB"
WWWPATH="/var/www"
APACHE="www-se3"
CGIPATH="/usr/lib/cgi-binse"
SMBCONF="/etc/samba/smb.conf"
APACHECONF="/etc/apache/httpdse.conf"
PAMLDAPCONF="/etc/pam_ldap.conf"
NSSLDAPCONF="/etc/libnss-ldap.conf"
NSSWITCH="/etc/nsswitch.conf"
MRTGCFG="/etc/mrtg.cfg"
INITDSAMBA="/etc/init.d/samba"
INITDAPACHE="/etc/init.d/apache2se"

#
# Récupération des paramètres de connexion à la base
#


if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible."
	exit 1
fi
LDAPIP=`echo "SELECT value FROM params WHERE name='ldap_server'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$LDAPIP" ]; then
	echo "Impossible d'accéder aux paramètres SambaEdu."
	exit 1
fi

#
# Récupération de la version SambaEdu en cours
#

MAJNBR=`echo "SELECT value FROM params WHERE name='majnbr'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
MAJNBRORI=$MAJNBR
if [ -z "$MAJNBR" ]; then
	MAJNBR=0
fi

if [ -z $(grep "3.1" /etc/debian_version) ]; then
	[ $MAJNBR -le 100 ] && MAJNBR=100
fi

if [ $MAJNBR -le 120 ]; then
	NEXT_MINOR_MAJ=$MAJNBR
else
	NEXT_MINOR_MAJ=$(( $MAJNBR+1 ))
fi
NEXT_MAJOR_MAJ=$(( (($MAJNBR /10) * 10) + 10 ))


if [ ! -e maj/maj$NEXT_MINOR_MAJ.sh ] && [ ! -e maj/maj$NEXT_MAJOR_MAJ.sh ] ; then
	
  if [ ! "$CONT" = "C" ]; then
	  echo "Aucune mise à jour disponible. Fin du processus."
	  exit 0
	fi
fi

#
# Mise à jour des scripts et de l'interface
#

#
# Lancement des scripts de Mise à jour
#


while [ -e maj/maj$NEXT_MINOR_MAJ.sh  ] || [ -e maj/maj$NEXT_MAJOR_MAJ.sh ]; do

  if [ -e maj/maj$NEXT_MINOR_MAJ.sh ]
  then
    MAJNBR=$NEXT_MINOR_MAJ
  elif [ -e maj/maj$NEXT_MAJOR_MAJ.sh ]
  then
    MAJNBR=$NEXT_MAJOR_MAJ
  fi

  # Application du script de maj
  echo "Application du script Maj$MAJNBR le $LADATE" | tee -a $LOG_DIR/log_maj$MAJNBR
  . maj/maj$MAJNBR.sh | tee -a $LOG_DIR/log_maj$MAJNBR
  NEXT_MINOR_MAJ=$(( $MAJNBR+1 ))
  NEXT_MAJOR_MAJ=$(( (($MAJNBR /10) * 10) + 10 ))
done

#let MAJNBR+=1

#
# Mise a jour du Numero de version
#
VERSION=`cat version`
echo "DELETE FROM params WHERE name=\"version\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
echo "INSERT INTO params (\`name\`,\`value\`,\`descr\`,\`cat\`) VALUES ('version',\"$VERSION\",'No de version','4')"| mysql -h $dbhost $dbname -u $dbuser -p$dbpass  	
echo "UPDATE params SET value=\"$MAJNBR\" WHERE name=\"majnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
echo "Mise à jour vers la version $VERSION achevée."

#
# Mise a jour des infos statistiques de version
#
REGISTRED=`echo "SELECT value FROM params WHERE name='registred'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ "$REGISTRED" = "1" ]; then
	wget http://wawadeb.crdp.ac-caen.fr/majse3/regmaj.php?old=$MAJNBRORI
	wget http://wawadeb.crdp.ac-caen.fr/majse3/regmaj.php?newv=$MAJNBR
	/bin/rm regmaj.php*
fi
echo -e "$COLTXT"

