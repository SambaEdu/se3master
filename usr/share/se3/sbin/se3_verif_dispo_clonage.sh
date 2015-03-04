#!/bin/bash

# $Id: se3_verif_dispo_clonage.sh 8494 2015-03-02 23:43:46Z keyser $
# Auteur:Franck Molle
# Dernière modification: 02/2014

##### Permet de créer un compte d'assistance pour l'interface web pdt 1 heure #####
###

. /usr/share/se3/includes/config.inc.sh -d
. /usr/share/se3/includes/functions.inc.sh

COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m"     # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

# Parametres
timestamp=$(date +%s)
timedate=$(date "+%Y%m%d_%H%M%S")



tmp="/var/se3/tmp_clonage_${timedate}"
mkdir -p "$tmp"
chmod 700 $tmp
cd $tmp



## Partie udpcast##

# ========================================

if [ -n "$udpcast_noyo_version" ]; then
	
	src_udpcast="http://wawadeb.crdp.ac-caen.fr/iso/udpcast"
	
	# Valeurs des versions en place recuperees de se3db.params:
	version_noyo_udpcast_en_place="$udpcast_noyo_version"
	version_initrd_udpcast_en_place="$udpcast_initrd_version"
	version_noyo_old_udpcast_en_place="$udpcast_noyo_old_version"
	version_initrd_old_udpcast_en_place="$udpcast_initrd_old_version"

	# ========================================

	rm -f versions.txt
	wget $src_udpcast/versions.txt > /dev/null 2>&1


	if [ "$?" = 0 -a -e versions.txt ]; then
		version_noyo_udpcast_en_ligne=$(grep ";vmlu26$" versions.txt | cut -d";" -f1)
		version_initrd_udpcast_en_ligne=$(grep ";udprd$" versions.txt | cut -d";" -f1)
		version_noyo_old_udpcast_en_ligne=$(grep ";vmlu26.old$" versions.txt | cut -d";" -f1)
		version_initrd_old_udpcast_en_ligne=$(grep ";udprd.old$" versions.txt | cut -d";" -f1)
		

		if [ "$version_noyo_udpcast_en_ligne" = "$version_noyo_udpcast_en_place" -a "$version_initrd_udpcast_en_ligne" = "$version_initrd_udpcast_en_ligne" ]; then
			echo "Maj udpcast ok"
			SETMYSQL udpcast_ajour "1" "Temoin de mise a jour udpcast" 7
		else
			echo "Maj udpcast Ko"
			SETMYSQL udpcast_ajour "0" "Temoin de mise a jour udpcast" 7
		fi
	else
		echo "ECHEC du telechargement du fichier des versions."
		SETMYSQL slitaz_ajour "2" "Temoin de mise a jour slitaz" 7
	fi
fi


## Partie Slitaz ##

if [ -n "$slitaz_noyo_version" ]; then
	src_slitaz="http://wawadeb.crdp.ac-caen.fr/iso/slitaz"
	
	# Valeurs des versions en place recuperees de se3db.params:
	version_noyo_slitaz_en_place="$slitaz_noyo_version"
	version_rootfs_slitaz_en_place="$slitaz_roofs_version"

	rm -f versions.txt
	wget $src_slitaz/versions.txt > /dev/null 2>&1
	if [ "$?" = 0 -a -e versions.txt ]; then

		version_noyo_slitaz_en_ligne=$(grep ";bzImage$" versions.txt | cut -d";" -f1)
		version_rootfs_slitaz_en_ligne=$(grep ";rootfs.gz$" versions.txt | cut -d";" -f1)
		
		
		if [ "$version_noyo_slitaz_en_ligne" = "$version_noyo_slitaz_en_place" -a "$version_rootfs_slitaz_en_ligne" = "$version_rootfs_slitaz_en_place" ]; then
			echo "Maj slitaz ok"
			SETMYSQL slitaz_ajour "1" "Temoin de mise a jour slitaz" 7
		else
			echo "Maj slitaz Ko"
			SETMYSQL slitaz_ajour "0" "Temoin de mise a jour slitaz" 7
		fi
	else
		echo "ECHEC du telechargement du fichier des versions slitaz ."
		SETMYSQL slitaz_ajour "2" "Temoin de mise a jour slitaz" 7
	fi
fi

# ========================================
	
	
	
## Partie sysrescd linux##

if [ -n "$srcd_version" ]; then

	src_sysresccd="http://wawadeb.crdp.ac-caen.fr/iso/sysresccd"
	version_srcd_en_place="$srcd_version"
	version_autorun2_en_place="$srcd_autorun2_vers"
	version_scripts_en_place="$srcd_scripts_vers"

	rm -f versions.txt
	wget $src_sysresccd/versions.txt > /dev/null 2>&1
	if [ "$?" = 0 -a -e versions.txt ]; then
		version_srcd_en_ligne=$(grep ";systemrescuecd.iso$" versions.txt | cut -d";" -f1)
		version_autorun2_en_ligne=$(grep ";autorun2$" versions.txt | cut -d";" -f1)
		version_scripts_en_ligne=$(grep ";scripts.tar.gz$" versions.txt | cut -d";" -f1)

		if [ "$version_srcd_en_ligne" = "$version_srcd_en_place" -a "$version_autorun2_en_ligne" = "$version_autorun2_en_place" -a "$version_scripts_en_ligne" = "$version_scripts_en_place" ]; then
			echo "Maj System Rescue CD ok"
			SETMYSQL rescd_ajour "1" "Temoin de mise a jour System Rescue CD" 7
		else
			echo "Maj System Rescue CD Ko"
			SETMYSQL rescd_ajour "0" "Temoin de mise a jour System Rescue CD" 7
		fi

		
	else
		echo "ECHEC du telechargement du fichier des versions System Rescue CD ."
		SETMYSQL rescd_ajour "2" "Temoin de mise a jour System Rescue CD" 7
	fi

fi


## Partie client linux##

if [ -n "$VarchPxeClientLin" ]; then

	VscriptPxeClientLin_en_place="$VscriptPxeClientLin"
	VarchPxeClientLin_en_place="$VarchPxeClientLin"
	
	src_client_linux="http://wawadeb.crdp.ac-caen.fr/iso/client_linux"
	rm -f versions.txt
	wget $src_client_linux/versions.txt > /dev/null 2>&1
	
	if [ "$?" = 0 -a -e versions.txt ]; then
	VscriptPxeClientLin_en_ligne=$(grep ";install_client_linux_mise_en_place.sh$" versions.txt | cut -d";" -f1)
	VarchPxeClientLin_en_ligne=$(grep ";install_client_linux_archive-tftp.tar.gz$" versions.txt | cut -d";" -f1)


		if [ "$VscriptPxeClientLin_en_ligne" = "$VscriptPxeClientLin_en_place" -a "$VarchPxeClientLin_en_ligne" = "$VarchPxeClientLin_en_place" ]; then
			echo "Maj client linux ok"
			SETMYSQL clinux_ajour "1" "Temoin de mise a jour client linux" 7
		else
			echo "Maj client linux Ko"
			SETMYSQL clinux_ajour "0" "Temoin de mise a jour client linux" 7
		fi

		
	else
		echo "ECHEC du telechargement du fichier des versions client linux."
		SETMYSQL clinux_ajour "2" "Temoin de mise a jour client linux" 7
	fi
		
fi