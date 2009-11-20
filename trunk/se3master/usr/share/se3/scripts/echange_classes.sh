#!/bin/sh

#Nom du dossier d'échange:
echange="_echange"
statut=""

ladate=$(date +"%Y.%m.%d-%H.%M.%S")

chemin_levee="/tmp"

if [ ! -z "$1" -a -e "/var/se3/Classes/$1" ]; then

	if echo "$1" | grep "Classe_grp" > /dev/null ; then
		GRP_CLASSE=$(echo "$1" | sed -e "s/^Classe_grp_//")
	else
		GRP_CLASSE="$1"
	fi
	
	if [ "$2" = "etat" ]; then
		if [ ! -e "/var/se3/Classes/$1/$echange" ]; then
			statut="Non encore initialisé"
		else
			if getfacl /var/se3/Classes/$1/$echange 2> /dev/null | grep "^group:$GRP_CLASSE:rwx$" > /dev/null; then
				statut="actif"
			else
				statut="verrouille"
			fi
		fi
	else
		case "$2" in
			"verrouille")

				if [ ! -e "/var/se3/Classes/$1/$echange" ]; then
					mkdir -p /var/se3/Classes/$1/$echange
					chown admin:nogroup /var/se3/Classes/$1/$echange
					chmod 770 /var/se3/Classes/$1/$echange
				fi

				#Tous les droits pour tous les Profs
				#(certains ne voient pas nécessairement le dossier var/se3/Classes/$1)
				#c'est pourquoi je ne me suis pas embêté à trier quels profs...

				#setfacl -R -m g:Profs:rwx /var/se3/Classes/$1/$echange
				#setfacl -R -m d:g:Profs:rwx /var/se3/Classes/$1/$echange
				#Cela serait inutile... droits hérités de /var/se3/Classes/$1
				#Quoique... ça ne passe pas par Samba.

				#Levée des droits pour tous les membres de la classe $1
				setfacl -R -x g:$GRP_CLASSE /var/se3/Classes/$1/$echange
				setfacl -R -x d:g:$GRP_CLASSE /var/se3/Classes/$1/$echange

				#Interdiction d'accès à public:
				setfacl -m g:$GRP_CLASSE:r /var/se3/Docs/public
			;;
			"actif")
																
				if [ ! -e "/var/se3/Classes/$1/$echange" ]; then
					mkdir -p /var/se3/Classes/$1/$echange
					chown admin:nogroup /var/se3/Classes/$1/$echange
					chmod 770 /var/se3/Classes/$1/$echange
				fi

				#echo "getfacl /var/se3/Classes/$1/$echange"
				getfacl /var/se3/Classes/$1/$echange

				#Tous les droits pour tous les membres de la classe $1
				#echo "setfacl -R -m g:$GRP_CLASSE:rwx /var/se3/Classes/$1/$echange"
				setfacl -R -m g:$GRP_CLASSE:rwx /var/se3/Classes/$1/$echange
				#echo "setfacl -R -m d:g:$GRP_CLASSE:rwx /var/se3/Classes/$1/$echange"
				setfacl -R -m d:g:$GRP_CLASSE:rwx /var/se3/Classes/$1/$echange

				#Levée de l'interdiction d'accès à public:
				#setfacl -m g:$1:rwx /var/se3/Docs/public
				#echo "setfacl -x g:$GRP_CLASSE /var/se3/Docs/public"
				setfacl -x g:$GRP_CLASSE /var/se3/Docs/public
			;;
		esac
	fi
else
	#ERREUR: Pour le moment, je ne fais qu'une et une seule classe.
	echo "USAGE: Passer en paramètre \$1 le nom du dossier de classe"
	echo "       (correctement orthographié;o)."
	echo "       Et en paramètre \$2 'actif' pour autoriser l'accès à $echange"
	echo "       et n'importe quoi d'autre pour désactiver."
fi

echo $statut

