#!/bin/bash
#
##### Script lanceur d'applications #####
#
# Auteur: St�phane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# /usr/share/se3/sbin/lanceur_applications.sh
# Derni�re modification: 07/06/2006

if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script permettant de lancer des applications lors du login"
	echo "via root preexec..."
	echo ""
	echo "Usage : pas d'option"
	exit
fi	

# Lanceur d'applications.
# Mis au point pour lancer une g�n�ration de fond d'�cran au format BMP
# avec annotation de l'image d'apr�s les informations r�cup�r�es du /etc/samba/smb.conf

# Dans la section [netlogon] pour Win9x et dans [homes] pour Win2K/WinXP:
# root preexec = ...;/usr/share/se3/sbin/lanceur_applications.sh %u %m %I %a %T

# Dossier contenant les scripts:
dossier_script="/usr/share/se3/scripts"

# Pour activer l'affichage d'infos...
debug=1

affich_debug(){
	if [ "$debug" = "1" ]; then
		echo "$1"
	fi
}

# Valeur tmp:
ladate=$(date +"%Y.%m.%d-%H.%M.%S")

# R�cup�ration des param�tres:
utilisateur="$1"
machine="$2"
ip="$3"
arch="$4"
date="$5"

# Lancement du script:
$dossier_script/genere_fond.sh $utilisateur $machine $ip $arch $date
# J'ai d�plac� la gestion des fichiers de LOCK dans le script genere_fond.sh
# NOTE: Les champs $machine $ip $arch $date ne sont plus utilis�s dans la version actuelle.

# Ajouter le lancement d'autres scripts si n�cessaire...
if [ -e $dossier_script/mes_commandes_perso.sh ]; then
	$dossier_script/mes_commandes_perso.sh $utilisateur $machine $ip $arch $date
fi
