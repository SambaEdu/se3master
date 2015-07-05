#!/bin/bash


## $Id$ ##



SMB_CONF=/etc/samba/smb_etab.conf
SE3_ROOT=/var/se3
MAIL=$(ldapsearch -xLLL "uid=admin" | grep mail | cut -d " " -f2)

NomPartage="$1"
Commentaire="$2"
Chemin="$SE3_ROOT/$3"
Admins="$4"
MachineCreation="$4"
Validite="$5"
DroitsAutres="$6"
GroupeProprio="$7"
DroitsGroupe="$8"

#Sauvegarde de l'ancien fichier de conf de Samba
cp -f "$SMB_CONF" "$SMB_CONF".share_orig

#Création du répertoire de partage
mkdir -p "$Chemin"
chown admin:admins $Chemin
setfacl -R -m "g:$GroupeProprio:$DroitsGroupe" "$Chemin"
setfacl -R -m "d:g:$GroupeProprio:$DroitsGroupe" "$Chemin"
setfacl -R -m "o:$DroitsAutres" "$Chemin"
setfacl -R -m "m::rwx" "$Chemin"

#Cherche la présence d'utilsateurs dans les paramétres
user_list=$(expr "$*" : '.*user_list=\(.*\)$')
#Cherche la présence d'un parc dans les paramétres
parc=$(expr "$*" : '.*parc=\([^ ]*\).*')
#Cherche la présence d'un admin dans les paramétres
admin=$(expr "$*" : '.*admin=\([^ ]*\).*')

(
 echo "#<$NomPartage>"
 echo "#Add with web SE3 admin interface from $MachineCreation"
 echo "#Date : $(date +"%Y-%m-%d %H:%R:%S")"
 echo "[$NomPartage]"
 echo "	comment	= $Commentaire"
 echo "	path	= $Chemin"
 echo "	read only	= No"
 if [ ! -z $user_list ]
 then
 	echo "	$Validite	= $user_list"
 fi
 if [ ! -z $admin ]
 then
  	echo "	admin users	= $admin"
 fi
 if [ ! -z $parc ]
 then
 	echo "	root preexec	= /usr/share/se3/sbin/machineInParc.pl %m $parc"
 	echo "	root preexec close	= Yes"
 fi
 echo "#</$NomPartage>"
) >> "$SMB_CONF"
 
#On envoie un mail à l'admin
echo "La création du partage $NomPartage sur le serveur $(hostname) a réussie!" | \
mail -s "[SE3 Tâche d'administration] Création partage Samba" $MAIL 

#On affiche le même message à l'écran
echo "La création du partage $NomPartage sur le serveur $(hostname) a réussie!"
