#!/bin/bash
#
##### Script permettant de remettre des droits corrects sur /home et /var/se3 #####
#
# Auteur : Stephane Boireau (Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# Derni�re modif: 12/09/2006

if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script permettant de remettre des droits corrects sur /home et /var/se3"
	echo ""
	echo "Usage : Pas d'option pour un usage classique."
        echo "        --home Pour juste remettre les droits sur les home"
	echo "        Si vous souhaitez remettre en place les ACL et proprios par d�faut de"
	echo "        /var/se3, passez 'acl_default' en param�tre."
	echo "        Pour en plus r�pondre par Oui � toutes les demandes de confirmation"
	echo "        lors de la restauration des acl par d�faut, passer 'auto' en deuxi�me"
	echo "        param�tre."
	exit
fi

#�Dossier pouvant contenir les ACL de /var/se3
dossier_svg="/var/se3/save"

HTML=0
if echo "$*" | grep "html" > /dev/null; then
    HTML=1
fi

if [ "$HTML" == "0" ]; then
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
else
    COLTITRE="<div style=\"color:black;\">"
    COLPARTIE="<div style=\"color:blue;\">"
    
    COLTXT="<div style=\"color:grey;\">"
    COLCHOIX="<div style=\"color:yellow;\">"
    COLDEFAUT="<div style=\"color:brown;\">"
    
    COLCMD="</div>"
    COLERREUR="<div style=\"color:red;\">"
    COLINFO="<div style=\"color:black;\">"
fi

if echo "$*" | grep "acl_default" > /dev/null; then
	acl_default="oui"
	if echo "$*" | grep "auto" > /dev/null; then
		auto="oui"
	fi
fi

[ "$HTML" == "0" ] && (
echo -e "$COLTITRE"
echo "#####################################################"
echo "# R�tablissement des droits et proprios sur /home/* #"
echo "#             sur /var/se3/Classes,...              #"
echo "#####################################################"
)

# Ajout pour r�tablir les droits
# sur les raccourcis provenant de skeluser:
echo -e "$COLCMD\c "
if [ -e "/tmp/raccourcis_skel_user" ]; then
	rm -fr /tmp/raccourcis_skel_user/*
else
	mkdir -p /tmp/raccourcis_skel_user
fi
cp -fr /etc/skel/user/profil/Bureau /tmp/raccourcis_skel_user/
cp -fr /etc/skel/user/profil/Demarrer /tmp/raccourcis_skel_user/



echo -e "$COLPARTIE"
echo "==================="
[ "$HTML" == "1" ] && echo "<br/>"
echo "Traitement de /home"
[ "$HTML" == "1" ] && echo "<br/>"
echo "==================="
			#### ligne 17 !!!! ###
echo -e "$COLCMD"
ls /home | while read A
do
	if [ -d "/home/$A" ]; then
		if [ "$A" != "templates" -a "$A" != "netlogon" -a "$A" != "admin" -a "$A" != "samba" -a "$A" != "sauvegarde" ]; then
			if [ ! -z "$(ldapsearch -xLLL uid=$A)" ]; then
				echo -e "$COLTXT\c "
				echo "Traitement de /home/$A"
				echo -e "$COLCMD\c "
				chown $A:admins /home/$A -R
				chmod 700 /home/$A -R

				# Ajout pour permettre � l'admin de d�poser des documents dans le Mes documents des utilisateurs et aux utilisateurs de voir ces documents.
				setfacl -R -m u:$A:rwx /home/$A/Docs

				# Droits sur le menu D�marrer.
				# Pour un bon fonctionnement du nettoyage avant application des templates:
				#chown root /home/$A/profil/Demarrer/Programmes/* -R
				chown root /home/$A/profil/Demarrer -R
				chmod 755 /home/$A/profil/Demarrer -R
				# Inconv�nient:
				# En l'�tat, les raccourcis provenant de skeluser
				# seront aussi supprim�s lors du prochain login.

				# Tous les raccourcis sur le Bureau seront supprim�s au prochain login:
				#chown root:admins /home/$A/profil/Bureau
				#chmod 777 /home/$A/profil/Bureau
				chown $A:admins /home/$A/profil/Bureau
				chmod 700 /home/$A/profil/Bureau
				find /home/$A/profil/Bureau/ -iname "*.lnk" | while read B
				do
					chown root:admins "$B" -R
					chmod 755 "$B"
				done

				# Dans profile, c'est lcs-users,... le groupe principal
				# de l'utilisateur qui est propri�taire.
				# Avec des droits � 700
				# Idem pour les fichiers cr��s par l'utilisateur dans son Home.

				# R�tablissement des raccourcis provenant
				# de /etc/skel/user/ soit X:\templates\skeluser\
				chown $A:admins /tmp/raccourcis_skel_user -R
				cp -fa /tmp/raccourcis_skel_user/Bureau/* /home/$A/profil/Bureau/ 2> /dev/null
				cp -fa /tmp/raccourcis_skel_user/Demarrer/* /home/$A/profil/Demarrer/ 2> /dev/null
				chown root:admins /home/$A/profil/Demarrer/Programmes
			fi
		fi

		if [ "$A" = "admin" ]; then
			echo -e "$COLTXT\c "
			echo "Traitement 'all�g�' de /home/$A"
			echo -e "$COLCMD\c "
			chown $A:admins /home/$A -R
			chmod 700 /home/$A -R
			# Pour admin, les raccourcis actuellement pr�sents dans le home ne seront pas supprim�s (c'est emb�tant).
			# Je pr�f�re tout de m�me qu'il fasse le m�nage lui-m�me.
		fi
	fi
done

# Nettoyage:
rm -fr /tmp/raccourcis_skel_user







echo -e "$COLPARTIE"
echo "============================="
[ "$HTML" == "1" ] && echo "<br/>"
echo "Traitements divers dans /home"
[ "$HTML" == "1" ] && echo "<br/>"
echo "============================="

# Remarque:
# Les droits indiqu�s ci-dessous ont �t� relev�s sur un SE3 Sarge fraichement install�.
# Certains droits semblent curieux, comme les 674 sur les registre.zrn

echo -e "$COLTXT"
echo "R�tablissement des droits sur /home/netlogon"
echo -e "$COLCMD\c "
chown admin:admins /home/netlogon
# chmod g+s /home/netlogon -------------------------> A VOIR
chmod 755 /home/netlogon
chown root:admins /home/netlogon/*
chmod 660 /home/netlogon/*
chown -R admin:admins "/home/netlogon/Default User"
chmod -R 755 "/home/netlogon/Default User"
chown admin:admins /home/netlogon/registre.vbs
chmod 644 /home/netlogon/registre.vbs
chmod 644 /home/netlogon/killexplorer.exe
chmod 644 /home/netlogon/majdll.exe

echo -e "$COLTXT"
echo "R�tablissement des droits sur /home/templates"
echo -e "$COLCMD\c "
chown admin:admins /home/templates
setfacl -m u:www-se3:rwx /home/templates
setfacl -m d:u:www-se3:rwx /home/templates
ls /home/templates/ | while read A
do
	if [ ! -h /home/templates/$A -a -d /home/templates/$A ]; then
		chown admin:admins /home/templates/$A
		chown admin:admins /home/templates/$A/*

		# Pour les dossiers 775 et pour les fichiers 674???
		chmod 775 /home/templates/$A/*

		if [ -e "/home/templates/$A/registre.zrn" ]; then
			chown www-se3:www-data /home/templates/$A/registre.zrn
			chmod 674 /home/templates/$A/registre.zrn
		fi
	fi

	# Il ne faut pas appliquer les modifs suivantes sur skeluser
	# Le lien ne pointe pas sur un dossier d'une partition XFS
	if [ ! -h /home/templates/$A ]; then
		setfacl -R -m u:www-se3:rwx /home/templates/$A
		setfacl -R -m d:u:www-se3:rwx /home/templates/$A
	fi
done

chown -R www-se3:admins /etc/skel/user
chmod -R 755 /etc/skel/user




# Droits sur /home/templates/... en cas de d�l�gation de parc.
echo -e "$COLCMD\c "
ladate=$(date +"%Y.%m.%d-%H.%M.%S")
tmp=/root/tmp/retablissement_delagation_parc.${ladate}
mkdir -p $tmp
echo "select * from delegation;" > $tmp/requete.sql
liste=($(mysql -uroot se3db < $tmp/requete.sql))
# Voici un exemple de retour:
# ID login parc niveau 4 hugov xp view 5 curiem w9x manage
# 0  1     2    3      4 5     6  7    8 9      10  11
if [ ${#liste[*]} -gt 4 ]; then
	echo -e "$COLTXT"
	echo "R�tablissement des d�l�gations de parcs..."
	nb_delegations=$((${#liste[*]}/4-1))
	cpt=1
	while [ $cpt -le $nb_delegations ]
	do
		user=${liste[$((4*$cpt+1))]}
		parc=${liste[$((4*$cpt+2))]}
		niveau=${liste[$((4*$cpt+3))]}
		echo -e "$COLTXT"
		echo "R�tablissement de la d�l�gation $niveau � $user sur $parc"
		echo -e "$COLCMD\c "
		#/usr/share/se3/scripts/delegate_parc.sh $parc $user $niveau
		/usr/share/se3/scripts/delegate_parc.sh "$parc" "$user" "delegate"
		cpt=$(($cpt+1))
	done

	echo -e "$COLINFO"
	echo "Pour les d�l�gations de parcs, les droits sur les dossiers de templates sont"
	echo "r�tablis."
	echo "Aucune modification n'a �t� effectu�e sur l'annuaire LDAP en ce qui concerne les"
	echo "droits 'parc_can_manage', 'parc_can_view'."
fi


if [ "$1" = "--home" ]
then
       echo "Fin du traitement de home"
        exit
fi


if [ -e "$dossier_svg/acl/varse3_acl.bz2" -a -z "$acl_default" ]; then
	echo -e "$COLPARTIE"
	echo "==============================="
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "Traitements des ACL de /var/se3"
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "==============================="

	echo -e "$COLTXT"
	echo "Restauration du fichier de sauvegarde des ACL:"
	echo "   $dossier_svg/acl/varse3_acl.bz2"
	echo "(si vous pr�f�rez restaurer les ACL par d�faut, relancez le script avec le"
	echo "param�tre 'acl_default')"
	cd $dossier_svg
	ladate=$(date +"%Y%m%d-%H%M%S")
	mkdir -p $dossier_svg/tmp_${ladate}
	cd $dossier_svg/tmp_${ladate}
	cp $dossier_svg/acl/varse3_acl.bz2 ./
	bzip2 -d varse3_acl.bz2
	cd /var/se3
	setfacl --restore=$dossier_svg/tmp_${ladate}/varse3_acl
else
	echo -e "$COLPARTIE"
	echo "=============================="
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "Traitement de /var/se3/Classes"
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "=============================="
    [ "$HTML" == "0" ] && (
	echo -e "$COLTXT"
	echo "Dans un premier temps, les dossiers de Classes proprement dites vont �tre"
	echo "trait�es."
	echo "Ensuite, il vous sera propos� de r�tablir aussi les droits et ACL pour les"
	echo "dossiers de Classe_grp."
	echo -e "$COLCMD"
    )
	#ls /var/se3/Classes | grep "Classe_" | while read A
	# J'exclus maintenant les Classe_grp trait�es plus loin avec le script de Franck.
	ls /var/se3/Classes | grep "Classe_" | grep -v "Classe_grp_" | while read A
	do
		echo -e "$COLTXT\c "
		echo "Traitement de /var/se3/Classes/$A"
		echo -e "$COLCMD\c "
		# Le premier cas ne doit plus se produire maintenant:
		if echo "$A" | grep "Classe_grp_" > /dev/null; then
			GROUPE=$(echo "$A" | sed -e "s/Classe_grp_//")
		else
			GROUPE="$A"
		fi
		chown admin:nogroup /var/se3/Classes/$A
		#chmod 770 /var/se3/Classes/$A
		chmod 700 /var/se3/Classes/$A

		chown admin:nogroup /var/se3/Classes/$A/*
		#chmod 770 /var/se3/Classes/$A/*
		chmod 700 /var/se3/Classes/$A/*

		# Nettoyage des ACL existantes:
		setfacl -R -b /var/se3/Classes/$A

		# D�commenter pour supprimer les ACL existantes sur le dossier:
		# setfacl -R -k -b /var/se3/Classes/$A

		# ACL pour le groupe Classe:
		#setfacl -m g:$GROUPE:rx  /var/se3/Classes/$A
		#setfacl -m g:$GROUPE:rx  /var/se3/Classes/$A/_travail
		#setfacl -m d:g:$GROUPE:rx  /var/se3/Classes/$A/_travail
		setfacl -m d:m::rwx /var/se3/Classes/$A
		setfacl -m m::rwx /var/se3/Classes/$A
		setfacl -m g:$GROUPE:rx  /var/se3/Classes/$A
		setfacl -m g:$GROUPE:rx  /var/se3/Classes/$A/_travail
		setfacl -m d:g:$GROUPE:rx  /var/se3/Classes/$A/_travail

		# ACL pour admins
		setfacl -m g:admins:rwx  /var/se3/Classes/$A
		setfacl -m d:g:admins:rwx  /var/se3/Classes/$A

		# ACL pour les �l�ves:
		if ! echo "$A" | grep "Classe_grp_" > /dev/null; then
			ls /var/se3/Classes/$A | while read B
			do
				# Si des dossiers autres que les dossiers �l�ves
				# (en dehors de _travail et _profs)
				# existent dans /var/se3/Classes/$A/
				# il va s'afficher des erreurs (sans cons�quences):
				if [ "$B" != "_travail" -a "$B" != "_profs" ]; then
					setfacl -R -m u:$B:rwx  /var/se3/Classes/$A/$B
					setfacl -R -m d:u:$B:rwx  /var/se3/Classes/$A/$B
				fi
				setfacl -m m::rwx /var/se3/Classes/$A/$B
			done
			# Pour les /var/se3/Classes/Classe_grp_XXX/YYYYY_1ES1
			# o� YYYYY est le login de l'�l�ve, les ACL n'ont pas � �tre r�tablies
			# puisque /var/se3/Classes/Classe_grp_XXX/YYYYY_1ES1
			# n'est qu'un lien vers /var/se3/Classes/Classe_1ES1/YYYYY
		fi

		# R�tablissement des ACL pour l'�quipe des profs:
		Equipe=$(echo "$A" | sed -e "s/^Classe_/Equipe_/")
		ldapsearch -xLLL cn=$Equipe member | grep "^member: uid=" | sed -e "s/^member: uid=//" | cut -d"," -f1 | while read B
		do
			setfacl -m u:$B:rx  /var/se3/Classes/$A
			setfacl -m d:u:$B:rwx  /var/se3/Classes/$A
			setfacl -R -m u:$B:rwx  /var/se3/Classes/$A/*
			setfacl -R -m d:u:$B:rwx  /var/se3/Classes/$A/*
		done
	done




	if ls /var/se3/Classes | grep Classe_grp > /dev/null; then
		REPONSE=""
		if [ "$auto" = "oui" ]; then
			REPONSE="o"
		fi
		while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
		do
			echo -e "$COLTXT"
			echo -e "Voulez-vous r�tablir les ACL sur les dossiers Classe_grp_*? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
			read REPONSE
		done

		if [ "$REPONSE" = "o" ]; then
			ls /var/se3/Classes/ | grep Classe_grp_ | sed -e "s/^Classe_grp_//" | while read A
			do
				echo -e "$COLTXT"
				echo "R�tablissement des droits sur Classe_grp_${A}..."
				echo -e "$COLCMD\c "
				/usr/share/se3/scripts/creer_grpclass.sh $A
			done
		fi
	fi


	echo -e "$COLPARTIE"
	echo "============================"
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "Traitement de /var/se3/Progs"
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "============================"

	# Voulez-vous r�tablir les ACL par d�faut sur tout /var/se3/Progs
	# Voulez-vous r�tablir les ACL par d�faut sur tout /var/se3/Docs
	# /var/se3/Docs/public
	# /var/se3/Docs/deploy
	# /var/se3/Docs/trombine
    [ "$HTML" == "0" ] && (
	echo -e "$COLTXT"
	echo "Il se peut que vous ayez adapt� � vos besoins les ACL dans /var/se3/Progs"
	echo "Si c'est le cas, r�pondez non � la question suivante."
	echo "Sinon, des proprios, droits et ACL standards seront remis en place."
    )
	REPONSE=""
	if [ "$auto" = "oui" ]; then
		REPONSE="o"
	fi
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous r�tablir les proprios/droits/ACL par d�faut"
		echo -e "sur tout /var/se3/Progs? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
		read REPONSE
	done

	if [ "$REPONSE" = "o" ]; then
		echo -e "$COLTXT"
		echo "R�tablissement des proprios/droits/ACL par d�faut sur tout /var/se3/Progs"
		echo -e "$COLCMD\c "
		chown admin:admins /var/se3/Progs
		chmod 775 /var/se3/Progs
		# Nettoyage des ACL:
		setfacl -b /var/se3/Progs
		# D�finition des ACL:
		setfacl -R -m m:rwx /var/se3/Progs
		setfacl -R -m d:m:rwx /var/se3/Progs
		setfacl -m g::rx /var/se3/Progs
		setfacl -m d:g::rx /var/se3/Progs
		setfacl -m o::rx /var/se3/Progs
		setfacl -m d:o::rx /var/se3/Progs
		setfacl -R -m g:admins:rwx /var/se3/Progs
		setfacl -R -m d:g:admins:rwx /var/se3/Progs
		# OK


		chown admin:admins /var/se3/Progs/rw
		chmod 775 /var/se3/Progs/rw
		setfacl -R -m d:u::rwx /var/se3/Progs/rw
		setfacl -R -m d:g::rwx /var/se3/Progs/rw
		setfacl -R -m d:o::rwx /var/se3/Progs/rw
		# OK 'admins' et mask sont trait�s r�cursivement plus haut.

		chown admin:admins /var/se3/Progs/ro
		chmod 775 /var/se3/Progs/ro
		setfacl -R -m d:u::rwx /var/se3/Progs/ro
		setfacl -R -m d:g::rx /var/se3/Progs/ro
		setfacl -R -m d:o::rx /var/se3/Progs/ro
		# OK 'admins' et mask sont trait�s r�cursivement plus haut.




		if [ -e "/var/se3/Progs/rw/inventaire" ]; then
			if [ -e "/var/se3/Progs/ro/startocs.vbs" ]; then
				chmod 755 /var/se3/Progs/ro/startocs.vbs
				setfacl -m g:admins:rwx /var/se3/Progs/ro/startocs.vbs
				# Mais ce droit n'est pas effectif:
				#group:admins:rwx                #effective:r-x
				setfacl -m m:rx /var/se3/Progs/ro/startocs.vbs
			fi


			# Demander � l'auteur de l'inventaire si cela convient:
			chmod -R 777 /var/se3/Progs/rw/inventaire
			setfacl -R -m u::rwx /var/se3/Progs/rw/inventaire
			setfacl -R -m d:u::rwx /var/se3/Progs/rw/inventaire
			setfacl -R -m g::rwx /var/se3/Progs/rw/inventaire
			setfacl -R -m d:g::rwx /var/se3/Progs/rw/inventaire
			setfacl -R -m g:admins:rwx /var/se3/Progs/rw/inventaire
			setfacl -R -m d:g:admins:rwx /var/se3/Progs/rw/inventaire

			if [ -e "/var/se3/Progs/rw/inventaire/Application/Config.csv" ]; then
				chmod 666 /var/se3/Progs/rw/inventaire/Application/Config.csv
			fi

			if [ -e "/var/se3/Progs/rw/inventaire/Application/OCSInventory.bmp" ]; then
				chmod 644 /var/se3/Progs/rw/inventaire/Application/OCSInventory.bmp
			fi

			for EXTENSION in exe dll bmp
			do
				setfacl -m m::rx /var/se3/Progs/rw/inventaire/Application/*.$EXTENSION
			done
		fi


		if [ -e "/var/se3/Progs/ro/inventory/deploy" ]; then
			# Il semble que le dossier ne soit pas l� sur ma version de test...
			# Quelles sont les ACL appropri�es pour ce dossier?
			# Sont-elles h�rit�es de /var/se3/Progs/ro ?
			#bidon="oui"
			chown -R admin:admins /var/se3/Progs/ro/inventory
			setfacl -R -m m:rwx /var/se3/Progs/ro/inventory
		fi


		chown -R admin:admins /var/se3/Progs/install
		chmod 771 /var/se3/Progs/install
		setfacl -R -m u:www-se3:rx /var/se3/Progs/install
		setfacl -R -m d:u:www-se3:rx /var/se3/Progs/install
		setfacl -R -m g:admins:rwx /var/se3/Progs/install
		setfacl -R -m d:g:admins:rwx /var/se3/Progs/install
		setfacl -m g::--- /var/se3/Progs/install/
		setfacl -m d:g::--- /var/se3/Progs/install/
		setfacl -R -m o::x /var/se3/Progs/install
		setfacl -m d:o::--- /var/se3/Progs/install/


		chown -R admin:admins /var/se3/Progs/install/9x
		chmod -R 770 /var/se3/Progs/install/9x
		setfacl -R -m u::rwx /var/se3/Progs/install/9x
		setfacl -R -m d:u::rwx /var/se3/Progs/install/9x
		setfacl -R -m u:www-se3:rx /var/se3/Progs/install/9x
		setfacl -R -m d:u:www-se3:rx /var/se3/Progs/install/9x
		setfacl -R -m g:admins:rwx /var/se3/Progs/install/9x
		setfacl -R -m d:g:admins:rwx /var/se3/Progs/install/9x
		setfacl -m g::--- /var/se3/Progs/install/9x
		setfacl -m d:g::--- /var/se3/Progs/install/9x


		chown -R admin:admins /var/se3/Progs/install/xp
		chmod -R 770 /var/se3/Progs/install/xp
		setfacl -R -m u::rwx /var/se3/Progs/install/xp
		setfacl -R -m d:u::rwx /var/se3/Progs/install/xp
		setfacl -R -m u:www-se3:rx /var/se3/Progs/install/xp
		setfacl -R -m d:u:www-se3:rx /var/se3/Progs/install/xp
		setfacl -R -m g:admins:rwx /var/se3/Progs/install/xp
		setfacl -R -m d:g:admins:rwx /var/se3/Progs/install/xp
		setfacl -m g::--- /var/se3/Progs/install/xp
		setfacl -m d:g::--- /var/se3/Progs/install/xp
		# Quelques fichiers ont normalement group::r ou group::rx

		chown -R admin:admins /var/se3/Progs/install/xp/Registry



		# Dans /var/se3/Progs/install/installdll, c'est un peu le bazar les droits...
		# Voir avec zorn...
		mkdir -p /var/se3/Progs/install/installdll
		#chown root:root /var/se3/Progs/install/installdll
		chown -R root:admins /var/se3/Progs/install/installdll
		setfacl -m o::rx /var/se3/Progs/install/installdll
		setfacl -m d:o::--- /var/se3/Progs/install/installdll
		chmod 640 /var/se3/Progs/install/installdll/*
		# activate-adminse3.sh confse3.in installcop.ba majzinbr mountappliz.exe netdom.exe registre.vbs.in setacl.exe todo.cm VB6FR.DLL
		for fich in ActivePerl-5.8.7.813-MSWin32-x86-148120.msi confse3.ini Copssh_1.3.6_Installer.exe id_rsa.pub
		do
			chown root:admins /var/se3/Progs/install/installdll/$fich
			chmod 670 /var/se3/Progs/install/installdll/$fich
		done

		for fich in CPAU.exe msi.vbs prerenewdll.vbs registre2K.MSI registre2K.MSI.cab registre.MSI registre.MSI.cab rejoinsambaedu3.vbs rejoin_se3_XP.vbs remote_adminse3_dontexpire.vbs renewdll.vbs
		do
			chown root:admins /var/se3/Progs/install/installdll/$fich
			chmod 674 /var/se3/Progs/install/installdll/$fich
		done

		if [ -e "/var/se3/Progs/install/installdll/clients.ini" ]; then
			chown admin:lcs-users /var/se3/Progs/install/installdll/clients.ini
			chmod 770 /var/se3/Progs/install/installdll/clients.ini
		fi

		if [ -e "/var/se3/Progs/install/installdll/installcop.bat" ]; then
			chown root:admins /var/se3/Progs/install/installdll/installcop.bat
			chmod 660 /var/se3/Progs/install/installdll/installcop.bat
		fi

		if [ -e "/var/se3/Progs/install/installdll/todo.cmd" ]; then
			chown root:admins /var/se3/Progs/install/installdll/todo.cmd
			chmod 660 /var/se3/Progs/install/installdll/todo.cmd
		fi

		# D�j� fait plus haut:
		#setfacl -R -m m:rwx /var/se3/Progs
		#setfacl -R -m d:m:rwx /var/se3/Progs
		#setfacl -R -m u:www-se3:rx /var/se3/Progs/install
		#setfacl -R -m d:u:www-se3:rx /var/se3/Progs/install
		#setfacl -R -m g:admins:rwx /var/se3/Progs
		#setfacl -R -m d:g:admins:rwx /var/se3/Progs
	fi




	echo -e "$COLPARTIE"
	echo "==========================="
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "Traitement de /var/se3/Docs"
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "==========================="
    [ "$HTML" == "0" ] && (
	echo -e "$COLTXT"
	echo "Il se peut que vous ayez adapt� � vos besoins les ACL dans /var/se3/Docs"
	echo "Si c'est le cas, r�pondez non � la question suivante."
	echo "Sinon, des proprios, droits et ACL standards seront remis en place."
    )
	REPONSE=""
	if [ "$auto" = "oui" ]; then
		REPONSE="o"
	fi
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous r�tablir les proprios/droits/ACL par d�faut"
		echo -e "sur tout /var/se3/Docs? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
		read REPONSE
	done

	if [ "$REPONSE" = "o" ]; then
		echo -e "$COLTXT"
		echo "R�tablissement des proprios/droits/ACL par d�faut sur tout /var/se3/Docs"
		echo -e "$COLCMD\c "

		#chown admin:root /var/se3/Docs -R
		chown admin:admins /var/se3/Docs
		chmod 775 /var/se3/Docs
		#setfacl -R -m m:rwx /var/se3/Docs
		#setfacl -R -m d:m:rwx /var/se3/Docs
		setfacl -R -m g:admins:rwx /var/se3/Docs
		setfacl -R -m d:g:admins:rwx /var/se3/Docs
		setfacl -m u::rwx /var/se3/Docs
		setfacl -m d:u::rwx /var/se3/Docs
		setfacl -m g::rx /var/se3/Docs
		setfacl -m d:g::rx /var/se3/Docs
		setfacl -m o::rx /var/se3/Docs
		setfacl -m d:o::rx /var/se3/Docs

		REPONSE=""
		if [ "$auto" = "oui" ]; then
			REPONSE="o"
		fi
		while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
		do
			echo -e "$COLTXT"
			echo -e "Voulez-vous mettre � 777 les droits r�cursivement"
			echo -e "sur tout le contenu de /var/se3/Docs/public? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
			read REPONSE
		done
		if [ "$REPONSE" = "o" ]; then
			OPT=" -R "
		else
			OPT=""
		fi
		echo -e "$COLCMD\c "

		# A l'int�rieur de Docs/public (sauf modif des ACL), tout le monde peut tout faire...
		chown $OPT admin:root /var/se3/Docs/public
		# ==================
		# Faut-il mettre -R?
		chmod $OPT 777 /var/se3/Docs/public
		#setfacl -m m:rwx /var/se3/Docs/public
		setfacl $OPT -m u::rwx /var/se3/Docs/public
		setfacl $OPT -m g::rwx /var/se3/Docs/public
		setfacl $OPT -m o::rwx /var/se3/Docs/public
		setfacl $OPT -m d:m:rwx /var/se3/Docs/public
		setfacl $OPT -m d:u::rwx /var/se3/Docs/public
		setfacl $OPT -m d:g::rwx /var/se3/Docs/public
		setfacl $OPT -m d:o::rwx /var/se3/Docs/public
		# ==================

		mkdir -p /var/se3/Docs/deploy
		chown admin:www-data /var/se3/Docs/deploy
		chmod 770 /var/se3/Docs/deploy
		#setfacl -m m:rwx /var/se3/Docs/deploy
		setfacl -m u::rwx /var/se3/Docs/deploy
		setfacl -m d:u::rwx /var/se3/Docs/deploy
		setfacl -m g::rx /var/se3/Docs/deploy
		setfacl -m d:g::rx /var/se3/Docs/deploy
		setfacl -m o::--- /var/se3/Docs/deploy
		setfacl -m d:o::rx /var/se3/Docs/deploy

		mkdir -p /var/se3/Docs/trombine
		chmod 700 /var/se3/Docs/trombine
		chown admin:admins /var/se3/Docs/trombine
		if [ ! -z "$(ls /var/se3/Docs/trombine)" ]; then
			chown -R admin:admins /var/se3/Docs/trombine/*
		fi
		setfacl -R -m g:admins:rwx /var/se3/Docs/trombine
		setfacl -R -m g:Profs:rx /var/se3/Docs/trombine
		setfacl -R -m d:g:admins:rwx /var/se3/Docs/trombine
		setfacl -R -m d:g:Profs:rx /var/se3/Docs/trombine
		setfacl -R -m u:www-se3:rx /var/se3/Docs/trombine
		setfacl -R -m d:u:www-se3:rx /var/se3/Docs/trombine
		setfacl -m u::rwx /var/se3/Docs/trombine
		setfacl -m d:u::rwx /var/se3/Docs/trombine
		setfacl -m g::rx /var/se3/Docs/trombine
		setfacl -m d:g::rx /var/se3/Docs/trombine
		setfacl -m o::--- /var/se3/Docs/trombine
		setfacl -m d:o::rx /var/se3/Docs/trombine

		if [ ! -z "$(ls /var/se3/Docs/trombine)" ]; then
			setfacl -m m::rx /var/se3/Docs/trombine/*
			setfacl -m u::rwx /var/se3/Docs/trombine/*
			setfacl -m g::rx /var/se3/Docs/trombine/*
			setfacl -m o::rx /var/se3/Docs/trombine/*
		fi

		mkdir -p /var/se3/Docs/media
		chown admin:admins /var/se3/Docs/media
		#chmod 755 /var/se3/Docs/media
		chmod u+rwx /var/se3/Docs/media
		chmod go+rx /var/se3/Docs/media
		setfacl -m m:rx /var/se3/Docs/media
		if [ -e "/var/se3/Docs/media/fonds_ecran" ]; then
			chmod -R 775 /var/se3/Docs/media/fonds_ecran
			setfacl -m m:rwx /var/se3/Docs/media/fonds_ecran
		fi
	fi



	echo -e "$COLPARTIE"
	echo "==========================="
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "Traitement de /var/se3/prof"
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "==========================="

	REPONSE=""
	if [ "$auto" = "oui" ]; then
		REPONSE="o"
	fi
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous r�tablir les proprios/droits/ACL par d�faut"
		echo -e "sur tout /var/se3/prof? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
		read REPONSE
	done

	if [ "$REPONSE" = "o" ]; then
		echo -e "$COLTXT"
		echo "R�tablissement des proprios/droits/ACL par d�faut sur tout /var/se3/prof"
		echo -e "$COLCMD\c "
		mkdir -p /var/se3/prof
		chown admin:Profs /var/se3/prof
		chmod -R 770 /var/se3/prof
		setfacl -R -m m:rwx /var/se3/prof
		setfacl -R -m d:m:rwx /var/se3/prof
		setfacl -R -m g:Profs:rwx /var/se3/prof
		setfacl -R -m d:g:Profs:rwx /var/se3/prof
		setfacl -m u::rwx /var/se3/prof
		setfacl -m d:u::rwx /var/se3/prof
		setfacl -m g::rwx /var/se3/prof
		setfacl -m d:g::rwx /var/se3/prof
		setfacl -m o::--- /var/se3/prof
		setfacl -m d:o::--- /var/se3/prof
	fi



	echo -e "$COLPARTIE"
	echo "================================="
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "Traitement de /var/se3/unattended"
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "================================="

	REPONSE=""
	if [ "$auto" = "oui" ]; then
		REPONSE="o"
	fi
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous r�tablir les proprios/droits/ACL par d�faut"
		echo -e "sur tout /var/se3/unattended? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
		read REPONSE
	done

	if [ "$REPONSE" = "o" ]; then
		echo -e "$COLTXT"
		echo "R�tablissement des proprios/droits/ACL par d�faut sur tout /var/se3/unattended"
		echo -e "$COLCMD\c "
		chown -R admin:admins /var/se3/unattended
		chmod -R 755 /var/se3/unattended
		setfacl -R -m u::rwx /var/se3/unattended/install/packages
		setfacl -R -m d:u::rwx /var/se3/unattended/install/packages
		setfacl -R -m g::rx /var/se3/unattended/install/packages
		setfacl -R -m d:g::rx /var/se3/unattended/install/packages
		setfacl -R -m o::rx /var/se3/unattended/install/packages
		setfacl -R -m d:o::rx /var/se3/unattended/install/packages
		setfacl -R -m u:www-se3:rx /var/se3/unattended/install/packages
		setfacl -R -m d:u:www-se3:rx /var/se3/unattended/install/packages
		setfacl -R -m u:unattend:rx /var/se3/unattended/install/packages
		setfacl -R -m d:u:unattend:rx /var/se3/unattended/install/packages
		setfacl -R -m m:rx /var/se3/unattended/install/packages
		setfacl -R -m d:m:rx /var/se3/unattended/install/packages

		setfacl -R -m u::rwx /var/se3/unattended/install/computers
		setfacl -R -m d:u::rwx /var/se3/unattended/install/computers
		setfacl -R -m g::rx /var/se3/unattended/install/computers
		setfacl -R -m d:g::rx /var/se3/unattended/install/computers
		setfacl -R -m o::rx /var/se3/unattended/install/computers
		setfacl -R -m d:o::rx /var/se3/unattended/install/computers
		setfacl -R -m u:www-se3:rx /var/se3/unattended/install/computers
		setfacl -R -m d:u:www-se3:rx /var/se3/unattended/install/computers
		setfacl -R -m u:unattend:rwx /var/se3/unattended/install/computers
		setfacl -R -m d:u:unattend:rwx /var/se3/unattended/install/computers
		setfacl -R -m m:rwx /var/se3/unattended/install/computers
		setfacl -R -m d:m:rwx /var/se3/unattended/install/computers

	fi



	echo -e "$COLPARTIE"
	echo "=============================="
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "Traitement de /var/se3/drivers"
    [ "$HTML" == "1" ] && echo "<br/>"
	echo "=============================="

	# Il y a /var/se3/drivers aussi drwxr-xr-x  2 admin   root     6 2006-04-13 19:40 drivers
	echo -e "$COLTXT"
	echo "R�tablissement des proprios/droits/ACL par d�faut sur /var/se3/drivers"
	echo -e "$COLCMD\c "
	chown admin:root /var/se3/drivers
	chmod 755 /var/se3/drivers
	# Pas d'ACL.
fi



# Scories de la version pr�c�dente du script:
#echo -e "$COLINFO"
#echo "Pour r�tablir les ACL sur le partage Classes pour les profs,"
#echo "lancez un 'Ressources et partages/Rafraichissement des classes'"
#echo "(en niveau Confirm�)."
#echo ""
#echo "Pour r�tablir les ACL sur des templates en cas de d�l�gation de parcs,"
#echo "consultez l'interface Web... et re-d�l�guez le Parc???"


echo -e "$COLPARTIE"
echo "===================="
[ "$HTML" == "1" ] && echo "<br/>"
echo "Lancement de permse3"
[ "$HTML" == "1" ] && echo "<br/>"
echo "===================="

echo -e "$COLTXT"
echo "Lancement de /usr/share/se3/scripts/permse3"
echo -e "$COLCMD\c "
/usr/share/se3/scripts/permse3

echo -e "$COLTITRE"
echo "***********"
echo "* Termin� *"
echo "***********"
echo -e "$COLTXT"


