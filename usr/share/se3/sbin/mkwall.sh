#!/bin/bash
#
##### Script de generation de fonds #####
#
# Auteur: Stephane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#
## Correctif du 2016-03-05 par Laurent Joly
#
# /usr/share/se3/sbin/mkwall.sh


	
chemin_param_fond="/etc/se3/fonds_ecran"
dossier_trombines="/var/se3/Docs/trombine"
dossier_base_fond="/var/se3/Docs/media/fonds_ecran"

#=============================================
# Chemin pour les images a inserer uploadees
dossier_tmp_fond="/var/lib/se3/fonds_ecran"
mkdir -p ${dossier_tmp_fond}
chown www-se3 ${dossier_tmp_fond}
# Comme ca, apres le premier essai, les droits sont ok pour l'upload

chemin_www_fonds_courants="Admin/fonds_ecran/courant"
dossier_www_fonds_courants="/var/www/se3/$chemin_www_fonds_courants"
mkdir -p ${dossier_www_fonds_courants}
#=============================================
image_a_inserer=""
if [ -e "$dossier_tmp_fond/tmp_$1.jpg" ]; then
	#echo "Le fichier image a inserer $dossier_tmp_fond/tmp_$1.jpg existe."
	image_a_inserer="$dossier_tmp_fond/tmp_$1.jpg"
fi
#=============================================

case $2 in 
jpg)
    prefix="jpeg:"
    ext="jpg"
;;
png)
    prefix="jpeg:"
    ext="jpg"
	/usr/bin/convert png:$image_a_inserer jpeg:$image_a_inserer
;;
gif)
    prefix="jpeg:"
    ext="jpg"
	/usr/bin/convert gif:$image_a_inserer jpeg:$image_a_inserer
;;
*)
    prefix="jpeg:"
    ext="jpg"
	/usr/bin/convert bmp3:$image_a_inserer jpeg:$image_a_inserer
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

#=============================================

temoin=""

# Parametres propres a un utilisateur/groupe:
if [ "$1" = "admin" ]; then
    if [ -e "$chemin_param_fond/fond_admin.txt" ]; then
        # Le statut actif sert a savoir si on souhaite utiliser les parametres de fonds pour cet utilisateur/groupe.
        # Cela permet de desactiver sans supprimer les preferences.
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
            # L'utilisateur a depasse son quota...
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
            # Utilisateur eleve
            # Dans le cas d'un eleve, le groupe Classe est prioritaire (pour l'image) sur le groupe eleves.
            classe=$(ldapsearch -xLLL "(&(memberuid=$1)(cn=Classe*))" cn | grep "^cn: " | sed -e "s/^cn: //"|head -n1)
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


# Si aucune generation de fond n'est prevue pour l'utilisateur courant, on quitte:
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

# Generation du fond commun s'il n'existe pas:
# il est genere en jpeg par l'interface, mais xp veut du bmp, il sera converti si besoin
if [ ! -e "${dossier_base_fond}/$orig.jpg" ]; then
       /usr/bin/convert -size ${largeur}x${hauteur} gradient:${couleur1}-${couleur2} jpeg:${dossier_base_fond}/$orig.jpg
fi

# S'il y a une image a inserer, on ne se preoccupe pas de ce que le fond existe deja
if [ -z "$image_a_inserer" ]; then
	# Si le fond existe deja on quitte
	
	if [ -f "${dossier_base_fond}/$1_$orig.jpg" -a -f "${dossier_base_fond}/$1.jpg" ]; then
		echo " fond deja cree pour $1"
		# Suppression du fichier de lock s'il existe:
		rm -f "/tmp/$1.fond.lck"
	
		exit 0
	fi
fi

# on efface les anciens
rm -f ${dossier_base_fond}/$1_*.jpg
rm -f ${dossier_base_fond}/$1_*.bmp

#===============================================================
# Generation de la chaine des infos a afficher:
chaine=""
if [ "$annotation_nom" = "1" ]; then
    nom_prenom=$(ldapsearch -xLLL uid=$1 cn | grep "^cn: " | sed -e "s/^cn: //")
    #chaine="$nom_prenom"
    chaine=$(echo "$nom_prenom" | tr "'ÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕ¦ÛÜÙÚÝ¾´áàâäãåçéèêëîïìíñôöðòóõ¨ûüùúýÿ¸" "_AAAAAAACEEEEIIIINOOOOOSUUUUYYZaaaaaaceeeeiiiinoooooosuuuuyyz" | sed -e "s|[^A-Za-z_ -]||g" | sed -e "s|Æ|AE|g" | sed -e "s|¼|OE|g" | sed -e "s|æ|ae|g" | sed -e "s|½|oe|g")
fi

if [ "$annotation_classe" = "1" ]; then
    if [ -z "$classe" ]; then
        # Cas d'un eleve dans le groupe overfill:
        classe=$(ldapsearch -xLLL "(&(memberUid=$1)(cn=Classe_*))" cn | grep "^cn: " | sed -e "s/^cn: //"|head -n1)
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

# Generation de l'image:
if [ "$(cat "$chemin_param_fond/annotations_${base}.txt" 2>/dev/null)" = "actif" ]; then
    /usr/bin/convert -fill ${couleur_txt} -pointsize $taille_police -draw "gravity North text 0,0 '$chaine'" ${dossier_base_fond}/$orig.jpg ${dossier_base_fond}/$1_$orig.jpg
    if [ "$(cat "$chemin_param_fond/photos_${base}.txt" 2>/dev/null)" = "actif" ]; then
        photo=$dossier_trombines/$1.jpg
		if [ -f "$photo" ]; then
            source $chemin_param_fond/dim_photo_$temoin.sh
            if [ "$dim_photo" -eq "0" ]; then
                taille_photo="100%"
            else
                taille_photo="${dim_photo}x${dim_photo}"
            fi
            /usr/bin/convert -resize $taille_photo $photo /tmp/$1_tromb.jpg
            /usr/bin/composite -gravity NorthEast -dissolve 80 /tmp/$1_tromb.jpg ${dossier_base_fond}/$1_$orig.jpg ${dossier_base_fond}/$1_$orig.jpg
            rm -f /tmp/$1_tromb.jpg
        fi
    fi
else
    # on fait une copie en bmp si besoin
    if [ ! -e "${dossier_base_fond}/$orig.jpg" ]; then
       /usr/bin/convert jpeg:${dossier_base_fond}/$orig.jpg bmp3:${dossier_base_fond}/$orig.bmp
    fi
fi

if [ -n "$image_a_inserer" -a -e "$image_a_inserer" ]; then
	# Insertion de l'image $image_a_inserer

	# Si il n'y a pas d'image propre a l'utilisateur on fait une copie du modèle du groupe de l'utilisateur
	# Et on va modifier la copie
	[ ! -f ${dossier_base_fond}/$1_$orig.jpg ] && cp ${dossier_base_fond}/$orig.jpg ${dossier_base_fond}/$1_$orig.jpg

	# Calculer la taille et redimensionner si c'est trop grand
	# Taille de l'image originale
	taille_image_inseree_orig=$(identify ${dossier_base_fond}/$1_$orig.jpg | cut -d" " -f3)
	larg_max=$(echo "$taille_image_inseree_orig" | cut -d"x" -f1)
	haut_max=$(echo "$taille_image_inseree_orig" | cut -d"x" -f2)
	echo "Largeur de l'image initiale: larg_max=$larg_max"
	echo "Hauteur de l'image initiale: haut_max=$haut_max"

	
	# Taille de l'image actuelle
	taille_image_inseree=$(identify $image_a_inserer | cut -d" " -f3)
	larg_ins=$(echo "$taille_image_inseree" | cut -d"x" -f1)
	haut_ins=$(echo "$taille_image_inseree" | cut -d"x" -f2)
	echo "Largeur de l'image proposee: larg_ins=$larg_ins"
	echo "Hauteur de l'image proposee: haut_ins=$haut_ins"

	#if [ $larg_ins -ge $larg_max -o $haut_ins -ge $haut_max ]; then
	#	taille_image_inseree="${larg_ins}x${haut_ins}"
	#fi

	redimensionnement="n"
	if [ $larg_ins -ge $larg_max -o $haut_ins -ge $haut_max ]; then
		redimensionnement="y"
		echo "Redimensionnement requis:"

		ratio_l=$(echo "$larg_ins/$larg_max"|bc -l)
		ratio_h=$(echo "$haut_ins/$haut_max"|bc -l)
		echo "Ratio horizontal: ratio_l=$ratio_l"
		echo "Ratio vertical: ratio_h=$ratio_h"

		ratio_l_test=$(echo "$ratio_l*1000000"|bc|cut -d"." -f1)
		ratio_h_test=$(echo "$ratio_h*1000000"|bc|cut -d"." -f1)
		#echo "ratio_l_test=$ratio_l_test"
		#echo "ratio_h_test=$ratio_h_test"

		#if [ $ratio_h -gt $ratio_l ]; then
		if [ $ratio_h_test -gt $ratio_l_test ]; then
			larg_ins=$(echo "$larg_ins/$ratio_h"|bc)
			haut_ins=$(echo "$haut_ins/$ratio_h"|bc)
		else
			larg_ins=$(echo "$larg_ins/$ratio_l"|bc)
			haut_ins=$(echo "$haut_ins/$ratio_l"|bc)
		fi

		taille_image_inseree="${larg_ins}x${haut_ins}"

	fi

	echo "Dimensions de l'image inseree: taille_image_inseree=$taille_image_inseree"

	# Opaque: 100, transparent:0
	niveau_dissolve=100

	if [ "$redimensionnement" = "y" ]; then
		echo "Redimensionnement si necessaire de l'image"
		#echo "/usr/bin/convert -resize $taille_image_inseree $image_a_inserer /tmp/$1_inser.jpg"
		/usr/bin/convert -resize $taille_image_inseree $image_a_inserer /tmp/$1_inser.jpg
	else
		echo "Copie temporaire de l'image a inserer"
		#echo "cp $image_a_inserer /tmp/$1_inser.jpg"
		cp $image_a_inserer /tmp/$1_inser.jpg
	fi

	echo "Fusion avec le fond"
	#echo "/usr/bin/composite -gravity center -dissolve $niveau_dissolve /tmp/$1_inser.jpg ${dossier_base_fond}/$1_$orig.jpg ${dossier_base_fond}/$1_$orig.jpg"
	/usr/bin/composite -gravity center -dissolve $niveau_dissolve /tmp/$1_inser.jpg ${dossier_base_fond}/$1_$orig.jpg ${dossier_base_fond}/$1_$orig.jpg
	# Ajout du texte en surimpression si nécessaire
	echo "Ajout du text en surimpression si necessaire"
	if [ "$(cat "$chemin_param_fond/annotations_${base}.txt" 2>/dev/null)" = "actif" ]; then
	/usr/bin/convert -fill ${couleur_txt} -pointsize $taille_police -draw "gravity North text 0,0 '$chaine'" ${dossier_base_fond}/$1_$orig.jpg ${dossier_base_fond}/$1_$orig.jpg
	fi
	rm -f /tmp/$1_inser.jpg
else
	# Si il n'y a pas d'image propre a l'utilisateur on cree un lien vers le modele du groupe de l'utilisateur
	[ ! -f ${dossier_base_fond}/$1_$orig.jpg ] && ln -s ${dossier_base_fond}/$orig.jpg ${dossier_base_fond}/$1_$orig.jpg
fi

# Generation du fond d'écran en bmp pour windows xp
echo "Generation du fond d'ecran en bmp pour windows xp"
/usr/bin/convert ${dossier_base_fond}/$1_$orig.jpg bmp3:${dossier_base_fond}/$1_$orig.bmp
# On supprime l'ancien lien symbolique login.jpg et login.bmp pour le recreer
rm -f  ${dossier_base_fond}/$1.bmp
rm -f  ${dossier_base_fond}/$1.jpg
ln -s ${dossier_base_fond}/$1_$orig.jpg ${dossier_base_fond}/$1.jpg
ln -s ${dossier_base_fond}/$1_$orig.bmp ${dossier_base_fond}/$1.bmp

# Pour pouvoir consulter le fond courant depuis l'interface web
# Probleme: Si ce n'est pas un jpeg, l'affichage merdoie...
if [ -n "$image_a_inserer" -a -e "$image_a_inserer" ]; then	
	echo "Creation du lien..."
	ln -s ${dossier_base_fond}/$1.jpg $dossier_www_fonds_courants/$1.jpg
	chown www-se3 $dossier_www_fonds_courants/$1.jpg
	rm -f $image_a_inserer
fi

# Changement des droits des fichiers
chown admin ${dossier_base_fond}/$1_$orig.jpg
chown admin ${dossier_base_fond}/$1_$orig.bmp
chown www-se3 $dossier_www_fonds_courants/$1.jpg
rm -f "/tmp/$1.fond.lck"
