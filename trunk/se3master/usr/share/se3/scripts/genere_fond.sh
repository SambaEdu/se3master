#!/bin/bash
#
##### Script de configuration de fonds #####
#
# Auteur: St�phane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# /usr/share/se3/scripts/genere_fond.sh
# Derni�re modification: 23/05/2006

if [ "$1" = "--help" -o "$1" = "-h" ]; then
    echo "Script permettant de configurer les fonds d'�cran..."
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

# Chemin de stockage des param�tres:
chemin_param_fond="/etc/se3/fonds_ecran"
# On y trouve/g�n�re un fichier de couleurs par d�faut.
# On y pr�cise si tel groupe obtient un fond annot� ou non.

# Cr�ation au besoin du dossier:
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
    echo -e "Ce script permet de g�n�rer un ${COLCMD}fond.jpg${COLINFO} lors du login sur SE3."
    echo -e "Il est possible de d�finir des fonds diff�rents sont propos�s pour:"
    echo -e "   - ${COLCMD}admin${COLINFO}"
    echo -e "   - ${COLCMD}Profs${COLINFO}"
    echo -e "   - ${COLCMD}Eleves${COLINFO}"
    echo -e "   - ${COLCMD}Classe_XXX${COLINFO}"
    echo -e "   - ${COLCMD}Administratifs${COLINFO}"
    echo -e "   - ${COLCMD}overfill${COLINFO}"
    echo -e "Le fond peut ou non �tre annot� avec les informations suivantes:"
    echo -e "  - le nom de l'utilisateur"
    echo -e "  - le pr�nom de l'utilisateur"
    echo -e "  - la classe de l'utilisateur"
    echo -e "L'annotation peut n'�tre activ�e que pour certains groupes."
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

# Param�tres communs:
generer_parametres_generation_fonds="non"
if [ -e "$chemin_param_fond/parametres_generation_fonds.sh" ]; then
    chmod +x "$chemin_param_fond/parametres_generation_fonds.sh"

    # R�cup�ration des variables communes:
    source "$chemin_param_fond/parametres_generation_fonds.sh"

    if [ -z "$largeur" ]; then
        generer_parametres_generation_fonds="oui"
    fi
fi

if [ ! -e "$chemin_param_fond/parametres_generation_fonds.sh" -o "$generer_parametres_generation_fonds" = "oui" ]; then
        echo "
# Dossier contenant les trames
# (image de fond commune � un des utilisateurs/groupes admin, profs, eleves)
dossier_base_fond="/var/se3/Docs/media/fonds_ecran"
mkdir -p ${dossier_base_fond}
# Woody ou Sarge:
# Pour Sarge, il faut sp�cifier 'bmp3:' pour le format BMP
prefixe=jpeg:

# Couleurs et dimensions par d�faut:
largeur=800
hauteur=600
couleur1=silver
couleur2=white
# Ces valeurs seront outrepass�es par les re-d�finitions ult�rieures." >> "$chemin_param_fond/parametres_generation_fonds.sh"

    chmod +x "$chemin_param_fond/parametres_generation_fonds.sh"

    # R�cup�ration des variables communes:
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
            # Il s'agit ici de g�n�rer un nouveau fond
            source "$chemin_param_fond/parametres_${3}.sh"
        ;;
        "image_fournie")
            # Passer en $3 le nom de l'image (sans le .jpg)
            if [ -e "$dossier_upload_images/$3.jpg" ]; then
                rm  -f "${dossier_base_fond}/$3.jpg"

                # Lorsque l'image est upload�e, le nom est forc� � $groupe.jpg,
                # m�me si l'image n'est pas de type JPG
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
            # NOTE: Dans le cas o� $1=variable_bidon, on se moque des annotations...
            #       Et si le fichier annotations_${3}.sh n'existe pas, le script s'arr�te l� sur une erreur.
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
