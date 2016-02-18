#!/bin/bash
#
##### Script d'installation de ImageMagick et Gsfonts #####
#
# Auteur: Stéphane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# /usr/share/se3/sbin/install_imagemagick_et_gsfonts.sh
# Dernière modification: 23/05/2006

if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script destiné à installer ImageMagick et gsfonts"
	echo "(et à renseigner des fichiers témoins)."
	echo ""
	echo "Usage : pas d'option"
	exit
fi	

# Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m"     # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

# Paramétres:
chemin_param_fond="/etc/se3/fonds_ecran"

# Création du dossier de paramétres:
mkdir -p $chemin_param_fond
chown www-se3:root $chemin_param_fond

# Dossier de log en cas de mode debug activé:
dossier_log="/var/log/se3/fonds_ecran"
mkdir -p "$dossier_log"

# Chemin des fichiers de lock (pour ne pas relancer le script
# tant que la première instance n'est pas terminée)
chemin_lock="/home/netlogon"
# Le fichier a l'extension 'lck' si bien que le script lckclean le nettoye en cas de p�pin.

# Création du fichier de LOCK:
touch $chemin_lock/installation_imagemagick_et_gsfonts.lck

# Valeur tmp:
ladate=$(date +"%Y.%m.%d-%H.%M.%S")

# Initialisation du témoin d'erreur
erreur=""

# Mode debug:
debug=1

affich_debug(){
	if [ "$debug" = "1" ]; then
		echo "$1"
	fi
}

ladate=$(date +"%Y.%m.%d-%H.%M.%S")

affich_debug "$ladate" >> $dossier_log/installation_${ladate}.log
affich_debug "Lancement de $0" >> $dossier_log/installation.log

# Installation si nécessaire des paquets requis:
if dpkg -l imagemagick | grep "ii  imagemagick" > /dev/null ; then
	echo -e "$COLINFO"
	echo "ImageMagick est bien installé."
	affich_debug "ImageMagick est bien installé." >> $dossier_log/installation_${ladate}.log
	if [ ! -e "$chemin_param_fond/imagemagick_present.txt" ]; then
		touch $chemin_param_fond/imagemagick_present.txt
	fi
else
	echo -e "$COLCMD"
	affich_debug "apt-get update" >> $dossier_log/installation_${ladate}.log
	apt-get update
	#affich_debug "apt-get install imagemagick" >> $dossier_log/installation_${ladate}.log
	#apt-get install imagemagick
	affich_debug "echo Y | apt-get install imagemagick" >> $dossier_log/installation_${ladate}.log
	echo Y | apt-get install imagemagick
	if [ "$?" = "0" ]; then
		echo -e "$COLTXT"
		echo "Installation de ImageMagick réussie."
		affich_debug "Installation de ImageMagick réussie." >> $dossier_log/installation_${ladate}.log
		touch $chemin_param_fond/imagemagick_present.txt
	else
		echo -e "$COLERREUR"
		echo "L'installation de ImageMagick a échoué."
		affich_debug "L'installation de ImageMagick a échoué." >> $dossier_log/installation_${ladate}.log
		affich_debug "===================================" >> $dossier_log/installation_${ladate}.log
		rm -f $chemin_lock/installation_imagemagick_et_gsfonts.lck
		erreur="ImageMagick"
		#exit
	fi
fi

if [ -z "$erreur" ]; then
	if dpkg -l gsfonts | grep "ii  gsfonts" > /dev/null ; then
		echo -e "$COLINFO"
		echo "Gsfonts est bien installé."
		affich_debug "Gsfonts est bien installé." >> $dossier_log/installation_${ladate}.log
		if [ ! -e "$chemin_param_fond/gsfonts_present.txt" ]; then
			touch $chemin_param_fond/gsfonts_present.txt
		fi
	else
		echo -e "$COLCMD"
		affich_debug "apt-get update" >> $dossier_log/installation_${ladate}.log
		apt-get update
		#affich_debug "apt-get install gsfonts" >> $dossier_log/installation_${ladate}.log
		#apt-get install gsfonts
		affich_debug "echo Y | apt-get install gsfonts" >> $dossier_log/installation_${ladate}.log
		echo Y | apt-get install gsfonts
		if [ "$?" = "0" ]; then
			echo -e "$COLTXT"
			echo "Installation de gsfonts réussie."
			affich_debug "Installation de gsfonts réussie." >> $dossier_log/installation_${ladate}.log
			touch $chemin_param_fond/gsfonts_present.txt
		else
			echo -e "$COLERREUR"
			echo "L'installation de gsfonts a échoué."
			affich_debug "L'installation de gsfonts a échoué." >> $dossier_log/installation_${ladate}.log
			affich_debug "===================================" >> $dossier_log/installation_${ladate}.log
			rm -f $chemin_lock/installation_imagemagick_et_gsfonts.lck
			erreur="gsfonts"
			#exit
		fi
	fi
	
	if [ -z "$erreur" ]; then
		if dpkg -l samba | grep 2.2.8a-se3 > /dev/null ; then
			# Il ne faut pas de préfixe pour la génération des BMP
			echo "2" > $chemin_param_fond/version_samba.txt
		else
			# Il faut un préfixe pour la génération des BMP
			#prefixe="bmp3:"
			echo "3" > $chemin_param_fond/version_samba.txt
		fi
		
		# Fichier permettant d'activer/désactiver la génération de fonds:
		touch $chemin_param_fond/actif.txt
		chown www-se3 $chemin_param_fond/actif.txt
		
		#Pour que le script soit exécutable:
		touch $chemin_param_fond/parametres_generation_fonds.sh
		chown www-se3 $chemin_param_fond/parametres_generation_fonds.sh
		chmod 750 $chemin_param_fond/parametres_generation_fonds.sh
		#Sinon, il est généré via l'interface web, mais les chmod() ne fonctionnent pas très bien en PHP.
	fi
fi

affich_debug "===================================" >> $dossier_log/installation_${ladate}.log

if [ ! -z "$erreur" ]; then
	mail_admin=$(ldapsearch -xLLL uid=admin mail | grep "^mail: " | sed -e "s/^mail: //")
	mail_ssmtp=$(grep "^root=" /etc/ssmtp/ssmtp.conf | cut -d"=" -f2)

	if [ ! -z "$mail_admin" ]; then
		mail $mail_admin -s "[Serveur SE3] Echec de l'installation de $erreur" < $dossier_log/installation_${ladate}.log
	fi

	if [ ! -z "$mail_ssmtp" ]; then
		mail $mail_ssmtp -s "[Serveur SE3] Echec de l'installation de $erreur" < $dossier_log/installation_${ladate}.log
	fi
fi

# Suppression du fichier de LOCK:
rm -f $chemin_lock/installation_imagemagick_et_gsfonts.lck

echo -e "$COLTXT"
