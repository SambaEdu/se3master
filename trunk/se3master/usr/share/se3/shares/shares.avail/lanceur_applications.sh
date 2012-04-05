#!/bin/bash
#shares_Win95: netlogon
#shares_Win2K: homes
#shares_WinXP: homes
#shares_Vista: homes
#shares_Seven: homes
#action: start
#level: 99
#
##### Script lanceur d'applications #####
#
# Auteur: Stéphane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#
## $Id: lanceur_applications.sh 2654 2007-12-30 21:37:00Z keyser $ ##
#
# /usr/share/se3/sbin/lanceur_applications.sh
# Dernière modification: 07/06/2006

if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script permettant de lancer des applications lors du login"
	echo "via root preexec..."
	echo ""
	echo "Usage : pas d'option"
	exit
fi	

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

# Récupération des paramètres:
utilisateur="$1"
machine="$2"
ip="$3"
arch="$4"
date="$5"

# Ajouter le lancement d'autres scripts si nécessaire...
if [ -e $dossier_script/mes_commandes_perso.sh ]; then
	$dossier_script/mes_commandes_perso.sh $utilisateur $machine $ip $arch $date
fi
