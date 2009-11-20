#!/bin/bash
#
## $Id$ ##
#
##### Met a jour Se3 depuis le dépot SVN #####
#


if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Permet la mise à jour du paquet se3 vers la version" 
    echo "en cours de développement"
	echo "Attention !!! Ne jamais lancer ce script sur un serveur en production"
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

ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$1"
	echo -e "$COLTXT"
	exit 1
}


POURSUIVRE()
{
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "O" -a "$REPONSE" != "n" ]
	do
		#echo -e "$COLTXT"
		echo -e "${COLTXT}Peut-on poursuivre ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done
echo -e "$COLTXT"
	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		ERREUR "Abandon!"
	fi
}


clear
echo -e "$COLTITRE"
echo "********************************************************************"
echo "* SCRIPT PERMETTANT LA MISE A JOUR DE SE3 VERS LA VERSION UNSTABLE *"
echo "* SOYEZ CONSCIENT DES RISQUES A LANCER CE SCRIPT !!!!              *"
echo "********************************************************************"

echo -e "$COLINFO"
echo "ATTENTION: Vous allez mettre à jour le paquet se3 vers la version" 
echo "en cours de développement."
echo "Ne jamais lancer ce script sur un serveur en production"


POURSUIVRE
if [ ! -e /usr/bin/svn ]; then
    echo -e "${COLINFO}Le paquet subversion n'est pas sur le système..."
    echo "Le script va donc procéder à son installation"
    POURSUIVRE
    apt-get update
    apt-get install subversion
fi
cd /root
mkdir -p se3master
echo "Importation du svn en cours....."
svn checkout https://svn.ac-grenoble.fr/svn/se3/se3master se3master || ERREUR "Impossible d'importer le svn distant"
PWDI=`pwd`

echo '
#!/usr/bin/perl
use strict;
use File::Find;
use File::Path;
sub enleve_moi_ca {
    if ($_ eq ".svn"){   
	rmtree($_) ;
	$File::Find::prune=1;
    }
}
find(\&enleve_moi_ca,".");
' > svnrmadm
chmod 700 svnrmadm

if [ -d build ]; then
	rm -r build
fi
mkdir build
cp -r se3master/* build
cd build
perl ../svnrmadm

# Remise en place des droits sur les fichiers

chmod 750 var/cache/se3_install -R
chmod 644 var/cache/se3_install/conf/*
chmod 644 var/cache/se3_install/reg/*
chmod 755 var/cache/se3_install/conf/apachese
chmod 600 var/cache/se3_install/conf/config.inc.php.in
chmod 600 var/cache/se3_install/conf/SeConfig.ph.in
chmod 600 var/cache/se3_install/conf/slapd_*.in
chmod 640 var/cache/se3_install/conf/mrtg.cfg
chmod 440 var/cache/se3_install/conf/sudoers

echo "Refabrication de  wwwse3.tgz"

cd var/cache/se3_install/wwwse3
tar -czf ../wwwse3.tgz se3
cd ..
rm -r wwwse3
cd ..

echo "Mise a jour de /var/cache/se3_install"
POURSUIVRE
rm -r /var/cache/se3_install
cp -a se3_install /var/cache
cd /var/cache/se3_install
#rm -r $PWDI/se3
echo "Lancement du script de maj"
POURSUIVRE
./maj_se.sh C

