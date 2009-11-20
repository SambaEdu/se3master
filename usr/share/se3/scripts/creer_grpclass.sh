#!/bin/bash

##### script permettant de créer les groupes classes tel groupes de techno / svt / idd en college##### 
# ou bien tpe en lycee,
# franck molle 03/2005
#$Id$

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
echo -e "$COLTITRE"
echo -e "Géneration des ressources pour les groupes IDD / Techno / TPE, etc ...."
echo -e "à partir d'un sous groupe créé avec l'interface"
echo -e "$COLTXT"

if [ -z $1 ]; then
ERREUR "Il faut donner le nom du groupe classe à créer en paramètre"
fi

TST_PARAM_OK=$(ldapsearch -xLLL cn="$1" | grep memberUid)
if [ -z "$TST_PARAM_OK" ]; then
ERREUR "Impossible de trouver le groupe passé en paramètre dans l'annuaire Ldap"
fi

CLASS_GRP=$(echo "Classe_grp_$1")

if [ ! -e /var/se3/Classes/$CLASS_GRP ]; then
mkdir /var/se3/Classes/$CLASS_GRP
chown admin:nogroup /var/se3/Classes/$CLASS_GRP
chmod 700 /var/se3/Classes/$CLASS_GRP
#Application des acl posix pour le groupe $CLASS_GRP
setfacl -m d:m::rwx /var/se3/Classes/$CLASS_GRP
setfacl -m m::rwx /var/se3/Classes/$CLASS_GRP
setfacl -m g:$1:rx /var/se3/Classes/$CLASS_GRP

# Application des acl posix pour le groupe admins SE3 sur l'ensemble de l'arborescence
setfacl -m d:g:admins:rwx /var/se3/Classes/$CLASS_GRP
setfacl -m g:admins:rwx /var/se3/Classes/$CLASS_GRP
fi

ldapsearch -xLLL cn="$1" | grep memberUid | cut -d " " -f2 | while read USER
do
echo -e "$COLTXT"
echo "Traitement de l'utilisateur $USER"
	GROUPS_USER=$(ldapsearch -xLLL memberUid="$USER" | grep "^cn" | cut -d " " -f2 | grep -v "$1")
	TEST_GRP_PROFS=$(echo "$GROUPS_USER" | grep Profs )
	if [ -z $TEST_GRP_PROFS ]; then
		CLASSE_USER=$(echo "$GROUPS_USER" | grep Classe)
		NOM_CLASSE=$(echo $CLASSE_USER | sed -e "s/Classe_//")
	        prenom=$(echo $USER | cut -s -d "." -f1)
	        nom=$(echo $USER | cut -s -d "." -f2)
	        if [ ! -z $nom ]; then
	                ELEVE=$(echo "$nom.$prenom")
	        else
	                ELEVE=$USER
	        fi 
		if [ ! -e /var/se3/Classes/$CLASS_GRP/${ELEVE}_$NOM_CLASSE ]; then
			echo -e "${COLCMD}Création du lien symbolique vers le répertoire classe de l'élève $ELEVE ${COLTXT}"
			ln -s /var/se3/Classes/$CLASSE_USER/$ELEVE /var/se3/Classes/$CLASS_GRP/${ELEVE}_$NOM_CLASSE
		else
			echo -e "${COLINFO}Le lien symbolique vers le répertoire classe de de l'élève $USER existe déjà ${COLTXT}"
		fi
		echo "/var/se3/Classes/$CLASSE_USER" >> /tmp/$1_list_rep_eleves
		echo "/var/se3/Classes/$CLASSE_USER/$ELEVE" >> /tmp/$1_list_rep_eleves
	else
		echo -e "${COLCMD}Mise en place des acls pour le prof $USER sur la ressource /var/se3/Classes/$CLASS_GRP ${COLCMD}"
		echo "$USER" >> /tmp/$1_list_profs
		setfacl -m d:u:$USER:rwx /var/se3/Classes/$CLASS_GRP
		setfacl -m u:$USER:rx /var/se3/Classes/$CLASS_GRP
	fi
	
	
done 
echo -e "$COLTXT"
cat /tmp/$1_list_profs | while read PROF
do
	echo -e "${COLCMD}Mise en place des acls pour le prof $PROF sur les répertoires classe des élèves ${COLTXT}"
	cat /tmp/$1_list_rep_eleves | while read REP_CLASS ; read REP_ELEV 
	do
# 	echo "setfacl -m u:$PROF:x $REP_CLASS"
# 	echo "setfacl -m u:$PROF:rwx $REP_ELEV"
		TEST_ACL=$(getfacl "$REP_CLASS" | grep "$PROF")
#		if [  -z "$TEST_ACL" ]; then
		
			setfacl -m u:$PROF:rx $REP_CLASS
			setfacl -m d:u:$PROF:rwx $REP_ELEV
#		else
			echo -e "$PROF a deja des acls sur $REP_CLASS"	
		
#		fi
	setfacl -m u:$PROF:rwx $REP_ELEV		
	done

done


# Creation du sous dossier travail
# c'est une obligation de le faire a ce niveau pour  que les profs
# aient les acls sur travail et profs par heritage
if [ ! -e /var/se3/Classes/$CLASS_GRP/_travail ]; then
mkdir /var/se3/Classes/$CLASS_GRP/_travail
chown admin:nogroup /var/se3/Classes/$CLASS_GRP/_travail
chmod 700 /var/se3/Classes/$CLASS_GRP/_travail
setfacl -m d:g:$1:rx /var/se3/Classes/$CLASS_GRP/_travail
setfacl -m g:$1:rx /var/se3/Classes/$CLASS_GRP/_travail
setfacl -m m::rwx /var/se3/Classes/$CLASS_GRP/_travail
fi	

if [ ! -e /var/se3/Classes/$CLASS_GRP/_profs ]; then
#  Creation du sous dossier professeurs
mkdir /var/se3/Classes/$CLASS_GRP/_profs
chown admin:nogroup /var/se3/Classes/$CLASS_GRP/_profs
chmod 700 /var/se3/Classes/$CLASS_GRP/_profs
setfacl -m m::rwx /var/se3/Classes/$CLASS_GRP/_profs
fi

rm -rf /tmp/$1_list_profs
rm -rf /tmp/$1_list_rep_eleves

echo -e "$COLTITRE"
echo -e "La ressource $1 a été créée dans /var/se3/classe/$CLASS_GRP"
echo -e "$COLTXT"

exit 0
