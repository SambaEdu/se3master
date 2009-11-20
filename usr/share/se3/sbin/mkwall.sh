#!/bin/bash
#
##### Script de génération de fonds #####
#
# Auteur: Stéphane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# /usr/share/se3/sbin/mkwall.sh


chemin_param_fond="/etc/se3/fonds_ecran"
dossier_trombines="/var/se3/Docs/trombine"
dossier_base_fond="/var/se3/Docs/media/fonds_ecran"
case $2 in 
jpg)
    prefix="jpeg:"
    ext="jpg"
;;
*)
    prefix="bmp3:"
    ext="bmp"
;;
esac

dim_photo=100
taille_police=30

if [ "$(cat $chemin_param_fond/actif.txt 2>/dev/null)" != "1" ]; then
        exit 0
fi

if [ -z "$1" -o ! -e "/usr/bin/convert" ]; then
    echo "Bad args or missing convert"
fi

[ -f "/tmp/$1.fond.lck" ] && exit 0
>"/tmp/$1.fond.lck"

temoin=

# Paramètres propres à un utilisateur/groupe:
if [ "$1" = "admin" ]; then
    if [ -e "$chemin_param_fond/fond_admin.txt" ]; then
        # Le statut actif sert à savoir si on souhaite utiliser les paramètres de fonds pour cet utilisateur/groupe.
        # Cela permet de désactiver sans supprimer les péréférences.
        if [ $(cat "$chemin_param_fond/fond_admin.txt") = "actif" ]; then
            source "$chemin_param_fond/parametres_admin.sh"
            source "$chemin_param_fond/annotations_admin.sh" 2>/dev/null
            temoin="admin"
        fi
    fi
    classe="Admins"
else
    if [ -e "$chemin_param_fond/fond_overfill.txt" ]; then
        test_membre_overfill=$(ldapsearch -xLLL "(&(memberuid=$1)(cn=overfill))" cn | grep "^cn: ")
        #if [ ! -z "$test_membre_overfill" -a $(cat "$chemin_param_fond/overfill.txt") = "actif" ]; then
        if [ ! -z "$test_membre_overfill" -a $(cat "$chemin_param_fond/fond_overfill.txt") = "actif" ]; then
            # L'utilisateur a dépassé son quota...
            if [ $(cat "$chemin_param_fond/fond_overfill.txt") = "actif" ]; then
                source "$chemin_param_fond/parametres_overfill.sh"
                source "$chemin_param_fond/annotations_overfill.sh" 2>/dev/null
                temoin="overfill"
            fi
        fi
    fi


    if [ -e "$chemin_param_fond/fond_Profs.txt" ]; then
        test_membre_prof=$(ldapsearch -xLLL "(&(memberuid=$1)(cn=Profs))" cn | grep "^cn: ")
        if [ ! -z "$test_membre_prof" ]; then
            # Utilisateur prof
            if [ $(cat "$chemin_param_fond/fond_Profs.txt") = "actif" ]; then
                source "$chemin_param_fond/parametres_Profs.sh"
                source "$chemin_param_fond/annotations_Profs.sh" 2>/dev/null
                temoin="Profs"
            fi
        fi
        classe="Profs"
    fi


    if [ -z "$temoin" ]; then
        # Utilisateur non prof... -> eleves ou administratifs?
        test_membre_eleve=$(ldapsearch -xLLL "(&(memberuid=$1)(cn=Eleves))" cn | grep "^cn: ")
        #echo "test_membre_eleve=$test_membre_eleve"
        if [ ! -z "$test_membre_eleve" ]; then
            # Utilisateur élève
            # Dans le cas d'un élève, le groupe Classe est prioritaire (pour l'image) sur le groupe eleves.
            classe=$(ldapsearch -xLLL "(&(memberuid=$1)(cn=Classe*))" cn | grep "^cn: " | sed -e "s/^cn: //")
            #echo "classe=$classe"
            if [ ! -z "$classe" ]; then
                if [ -e "$chemin_param_fond/fond_${classe}.txt" ]; then
                    if [ $(cat "$chemin_param_fond/fond_${classe}.txt") = "actif" ]; then
                        source "$chemin_param_fond/parametres_${classe}.sh"
                        source "$chemin_param_fond/annotations_${classe}.sh" 2>/dev/null
                        temoin=$classe
                    fi
                fi
            fi

            #if [ -z "$temoin" ]; then
            if [ -e "$chemin_param_fond/fond_Eleves.txt" -a -z "$temoin" ]; then
                if [ $(cat "$chemin_param_fond/fond_Eleves.txt") = "actif" ]; then
                    source "$chemin_param_fond/parametres_Eleves.sh"
                    source "$chemin_param_fond/annotations_Eleves.sh" 2>/dev/null
                    temoin="Eleves"
                fi
            fi
        fi
    fi


    if [ -e "$chemin_param_fond/fond_Administratifs.txt" -a -z "$temoin" ]; then
        # Utilisateur non prof... -> eleves ou administratifs?
        test_membre_administratifs=$(ldapsearch -xLLL "(&(memberuid=$1)(cn=Administratifs))" cn | grep "^cn: ")
        if [ ! -z "$test_membre_administratifs" ]; then
            # Utilisateur membre de: Administratifs
            if [ $(cat "$chemin_param_fond/fond_Administratifs.txt") = "actif" ]; then
                source "$chemin_param_fond/parametres_Administratifs.sh"
                source "$chemin_param_fond/annotations_Administratifs.sh" 2>/dev/null
                temoin="Administratifs"
            fi
        fi
        classe="Administratifs"
    fi
fi


# Si aucune génération de fond n'est prévue pour l'utilisateur courant, on quitte:
if [ -z "$temoin" ]; then
    echo " pas de fond pour $1"
    # Suppression du fichier de lock s'il existe:
    rm -f "/tmp/$1.fond.lck"
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

# Génération du fond commun s'il n'existe pas:
if [ ! -e "${dossier_base_fond}/$orig.$ext" ]; then
       /usr/bin/convert -size ${largeur}x${hauteur} gradient:${couleur1}-${couleur2} ${prefixe}${dossier_base_fond}/$orig.$ext
fi

# Si le fond existe deja on quitte

if [ -f "${dossier_base_fond}/$1_$orig.$ext" ]; then
    echo " fond deja cree pour $1"
    # Suppression du fichier de lock s'il existe:
    rm -f "/tmp/$1.fond.lck"

    exit 0
fi


# on efface les anciens
rm -f ${dossier_base_fond}/$1_*.$ext

#===============================================================
# Génération de la chaine des infos à afficher:
chaine=""
if [ "$annotation_nom" = "1" ]; then
    nom_prenom=$(ldapsearch -xLLL uid=$1 cn | grep "^cn: " | sed -e "s/^cn: //")
    chaine="$nom_prenom"
fi

if [ "$annotation_classe" = "1" ]; then
    if [ -z "$classe" ]; then
        # Cas d'un élève dans le groupe overfill:
        classe=$(ldapsearch -xLLL "(&(memberUid=$1)(cn=Classe_*))" cn | grep "^cn: " | sed -e "s/^cn: //")
    fi
    if [ -z "$classe" ]; then
        # Cas d'un prof dans le groupe overfill:
        classe=$(ldapsearch -xLLL "(&(memberUid=$1)(cn=Profs))" cn | grep "^cn: " | sed -e "s/^cn: //")
    fi
    if [ ! -z "$classe" ]; then
        if [ -n "${chaine}" ]; then
            chaine="$chaine ($classe)"
        else
            chaine="$classe"
        fi
    fi
fi

# Génération de l'image:
if [ "$(cat "$chemin_param_fond/annotations_${base}.txt" 2>/dev/null)" = "actif" ]; then
    /usr/bin/convert -fill ${couleur_txt} -pointsize $taille_police -draw "gravity North text 0,0 '$chaine'" ${dossier_base_fond}/$orig.jpg ${prefix}${dossier_base_fond}/$1_$orig.$ext
    if [ "$(cat "$chemin_param_fond/photos_${base}.txt" 2>/dev/null)" = "actif" ]; then
        if [ ! -z "$photo" ]; then
            source $chemin_param_fond/dim_photo_$temoin.sh
            if [ "$dim_photo" -eq "0" ]; then
                taille_photo="100%"
            else
                taille_photo="${dim_photo}x${dim_photo}"
            fi
            /usr/bin/convert -resize $taille_photo $photo /tmp/$1_tromb.jpg
            /usr/bin/composite -gravity NorthEast -dissolve 80 /tmp/$1_tromb.jpg ${prefix}${dossier_base_fond}/$1_$orig.$ext ${prefix}${dossier_base_fond}/$1_$orig.$ext
            rm -f /tmp/$1_tromb.jpg
        fi
    fi
fi
[ ! -f ${dossier_base_fond}/$1_$orig.$ext ] && ln -s ${dossier_base_fond}/$orig.$ext ${dossier_base_fond}/$1_$orig.$ext
rm -f  ${dossier_base_fond}/$1.$ext 
ln -s ${dossier_base_fond}/$1_$orig.$ext ${dossier_base_fond}/$1.$ext
chown admin ${dossier_base_fond}/$1_$orig.$ext
rm -f "/tmp/$1.fond.lck"
