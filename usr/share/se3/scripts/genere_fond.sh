#!/bin/bash
#
##### Script de configuration de fonds #####
#
# Auteur: Stéphane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#


## $Id$ ##


#
# /usr/share/se3/scripts/genere_fond.sh
# Dernière modification: 23/05/2006

if [ "$1" = "--help" -o "$1" = "-h" ]; then
    echo "Script permettant de configurer les fonds d'écran..."
    echo ""
    echo "Usage : plein d'options (### A PRECISER ###)"
    exit
fi


#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m"     # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

# Chemin de stockage des paramètres:
chemin_param_fond="/etc/se3/fonds_ecran"
# On y trouve/génère un fichier de couleurs par défaut.
# On y précise si tel groupe obtient un fond annoté ou non.

# Création au besoin du dossier:
mkdir -p "$chemin_param_fond"

# Dossier de stockage des fonds communs:
dossier_base_fond="/var/se3/Docs/media/fonds_ecran"
mkdir -p "$dossier_base_fond"

# Dossier d'upload des images:
#dossier_upload_images="/var/remote_adm"
dossier_upload_images="/etc/se3/www-tools"

# Valeur tmp
ladate=$(date +"%Y.%m.%d-%H.%M.%S")

nom_du_script=$(basename $0)

if [ -z "$1" -o ! -e "/usr/bin/convert" ]; then
    echo -e ""
    echo -e "${COLTITRE}INFO:${COLINFO}"
    echo -e "Ce script permet de générer un ${COLCMD}fond.jpg${COLINFO} lors du login sur SE3."
    echo -e "Il est possible de définir des fonds différents sont proposés pour:"
    echo -e "   - ${COLCMD}admin${COLINFO}"
    echo -e "   - ${COLCMD}Profs${COLINFO}"
    echo -e "   - ${COLCMD}Eleves${COLINFO}"
    echo -e "   - ${COLCMD}Classe_XXX${COLINFO}"
    echo -e "   - ${COLCMD}Administratifs${COLINFO}"
    echo -e "   - ${COLCMD}overfill${COLINFO}"
    echo -e "Le fond peut ou non être annoté avec les informations suivantes:"
    echo -e "  - le nom de l'utilisateur"
    echo -e "  - le prénom de l'utilisateur"
    echo -e "  - la classe de l'utilisateur"
    echo -e "L'annotation peut n'être activée que pour certains groupes."
    echo -e ""

    echo -e "$COLTXT"
    exit 0
fi

if [ ! -e "$chemin_param_fond/actif.txt" ]; then
    exit 0
fi

t=$(cat $chemin_param_fond/actif.txt 2>/dev/null)
if [ "$t" != "1" ]; then
    exit 0
fi

# Paramétres communs:
generer_parametres_generation_fonds="non"
if [ -e "$chemin_param_fond/parametres_generation_fonds.sh" ]; then
    chmod +x "$chemin_param_fond/parametres_generation_fonds.sh"

    # Récupération des variables communes:
    source "$chemin_param_fond/parametres_generation_fonds.sh"

    if [ -z "$largeur" ]; then
        generer_parametres_generation_fonds="oui"
    fi
fi

if [ ! -e "$chemin_param_fond/parametres_generation_fonds.sh" -o "$generer_parametres_generation_fonds" = "oui" ]; then
        echo "
# Dossier contenant les trames
# (image de fond commune à un des utilisateurs/groupes admin, profs, eleves)
dossier_base_fond="/var/se3/Docs/media/fonds_ecran"
mkdir -p ${dossier_base_fond}
# Woody ou Sarge:
# Pour Sarge, il faut spécifier 'bmp3:' pour le format BMP
prefixe=jpeg:

# Couleurs et dimensions par défaut:
largeur=800
hauteur=600
couleur1=silver
couleur2=white
# Ces valeurs seront outrepassées par les re-définitions ultérieures." >> "$chemin_param_fond/parametres_generation_fonds.sh"

    chmod +x "$chemin_param_fond/parametres_generation_fonds.sh"

    # Récupération des variables communes:
    source "$chemin_param_fond/parametres_generation_fonds.sh"
fi

if [ "$1" = "variable_bidon" ]; then
    case $2 in
        "nettoyer")
            if [ "$3" == "admin" ]
            then
                rm  -f "${dossier_base_fond}/Adminse3.jpg"
            else
                rm -f "${dossier_base_fond}/$3.jpg"
            fi
            # Il s'agit ici de générer un nouveau fond
            source "$chemin_param_fond/parametres_${3}.sh"
        ;;
        "image_fournie")
            # Passer en $3 le nom de l'image (sans le .jpg)
            if [ -e "$dossier_upload_images/$3.jpg" ]; then
                rm  -f "${dossier_base_fond}/$3.jpg"

                # Lorsque l'image est uploadée, le nom est forcé à $groupe.jpg,
                # même si l'image n'est pas de type JPG
                # Les lignes qui suivent assurent la conversion.
                if ! file $dossier_upload_images/$3.jpg | grep "JPEG" > /dev/null; then
                    mv $dossier_upload_images/$3.jpg $dossier_upload_images/$3.tmp
                    convert $dossier_upload_images/$3.tmp ${prefixe}${dossier_upload_images}/$3.jpg
                fi
		if [ "$3" == "admin" ]
		then
			mv $dossier_upload_images/$3.jpg "${dossier_base_fond}/Adminse3.jpg"
                	chown admin:root "${dossier_base_fond}/Adminse3.jpg"
		else
                	mv $dossier_upload_images/$3.jpg "${dossier_base_fond}/$3.jpg"
                	chown admin:root "${dossier_base_fond}/$3.jpg"
		fi
            fi
            #source "$chemin_param_fond/annotations_${3}.sh"
            temoin=""
            # NOTE: Dans le cas où $1=variable_bidon, on se moque des annotations...
            #       Et si le fichier annotations_${3}.sh n'existe pas, le script s'arrête là sur une erreur.
        ;;
	"supprimer")
		for file in /var/se3/Docs/media/fonds_ecran/[a-z]*.jpg
		do
			id="$(basename $file|sed -s 's/\.jpg//g')"
			if [ "$id" != "overfill" ]
			then
				rm -f "$file"
			fi
		done
		for file in /var/se3/Docs/media/fonds_ecran/[a-z]*.bmp
		do
			id="$(basename $file|sed -s 's/\.bmp//g')"
			if [ "$id" != "overfill" ]
			then
				rm -f "$file"
			fi
		done
	;;
	"genere_base")

		# Ou alors, mettre un témoin dans Consultation des paramétrages pour annoncer que tant que personne ne s'est connecté, on n'a pas le modèle?
		# C'est embétant pour faire les réglages.

		temoin=""

		if [ -z "$3" ]; then
			echo "ERREUR : \$3 est vide : $*"
			exit
		fi

		t=$(echo "$3"|sed -e "s/[A-Za-z0-9_]//g")
		if [ -n "$t" ]; then
			echo "ERREUR : La chaine '$3' comporte des caratères invalides: '$t'"
			exit
		fi

		if [ -e "$chemin_param_fond/fond_$3.txt" ]; then
			if [ $(cat "$chemin_param_fond/fond_$3.txt") = "actif" ]; then
				source "$chemin_param_fond/parametres_$3.sh"
				temoin=$3
			fi
		fi


		# Si aucune generation de fond n'est prevue pour l'utilisateur courant, on quitte:
		if [ -z "$temoin" ]; then
			echo " Pas de fond pour $3"
			# Suppression du fichier de lock s'il existe:
			rm -f "/tmp/$3.fond.lck"
			exit 0
		fi


		# Passage de variable:
		base=$temoin
		if [ "$base" == "admin" ]
		then
			orig="Adminse3"
		else
			orig="$base"
		fi

		/usr/bin/convert -size ${largeur}x${hauteur} gradient:${couleur1}-${couleur2} jpeg:${dossier_base_fond}/$orig.jpg
		#echo "/usr/bin/convert -size ${largeur}x${hauteur} gradient:${couleur1}-${couleur2} jpeg:${dossier_base_fond}/$orig.jpg" >> /var/log/se3_mkwall_debug.log

	;;
    esac
fi
if [ "$3" = "admin" ]; then
    rm -f $dossier_base_fond/admin.jpg
else
    ldapsearch -xLLL cn=$3 memberUid | grep "^memberUid: " | sed -e "s/^memberUid: //" | while read A
    do
        if [ -e "$dossier_base_fond/$A.jpg" ]; then
            rm -f $dossier_base_fond/$A.jpg
        fi
        if [ -e "$dossier_base_fond/$A.bmp" ]; then
            rm -f $dossier_base_fond/$A.bmp
        fi

    done
fi
