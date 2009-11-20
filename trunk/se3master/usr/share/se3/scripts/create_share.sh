#!/bin/bash
########################################
########################################

SMB_CONF=/etc/samba/smb.conf
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

#Cr�ation du r�pertoire de partage
mkdir -p "$Chemin"
chown admin:admins $Chemin
setfacl -R -m "g:$GroupeProprio:$DroitsGroupe" "$Chemin"
setfacl -R -m "d:g:$GroupeProprio:$DroitsGroupe" "$Chemin"
setfacl -R -m "o:$DroitsAutres" "$Chemin"
setfacl -R -m "m::rwx" "$Chemin"

#Cherche la pr�sence d'utilsateurs dans les param�tres
user_list=$(expr "$*" : '.*user_list=\(.*\)$')
#Cherche la pr�sence d'un parc dans les param�tres
parc=$(expr "$*" : '.*parc=\([^ ]*\).*')
#Cherche la pr�sence d'un admin dans les param�tres
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
 
#On envoie un mail � l'admin
echo "La cr�ation du partage $NomPartage sur le serveur $(hostname) a r�ussie!" | \
mail -s "[SE3 T�che d'administration] Cr�ation partage Samba" $MAIL 

#On affiche le m�me message � l'�cran
echo "La cr�ation du partage $NomPartage sur le serveur $(hostname) a r�ussie!"
