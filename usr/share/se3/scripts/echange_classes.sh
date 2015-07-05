#!/bin/bash


## $Id$ ##


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
				#c'est pourquoi je ne me suis pas embété à trier quels profs...

				#setfacl -R -m g:Profs:rwx /var/se3/Classes/$1/$echange
				#setfacl -R -m d:g:Profs:rwx /var/se3/Classes/$1/$echange
				#Cela serait inutile... droits hérités de /var/se3/Classes/$1
				#Quoique... ça ne passe pas par Samba.

				#Levée des droits pour tous les membres de la classe $1
				setfacl -R -x g:$GRP_CLASSE /var/se3/Classes/$1/$echange
				setfacl -R -x d:g:$GRP_CLASSE /var/se3/Classes/$1/$echange
				
				if [ -e "/etc/apache2/sites-enabled/webdav" ]; then
					setfacl -R -m u:www-data:rx,d:u:www-data:rx  /var/se3/Classes/$1/$echange
				fi
				

				if [ -e /var/www/se3/includes/config.inc.php ]; then
					dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
					dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
					dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
					dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
				else
					echo "Fichier de conf inaccessible"
					exit 1
				fi

				acces_partage_public=$(echo "SELECT value FROM params WHERE name='autoriser_partage_public';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
				if [ "$acces_partage_public" != "n" ]; then
					#Interdiction d'accés à public:
					setfacl -m g:$GRP_CLASSE:r /var/se3/Docs/public
				fi
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
				
				if [ -e "/etc/apache2/sites-enabled/webdav" ]; then
					setfacl -R -m u:www-data:rx,d:u:www-data:rx  /var/se3/Classes/$1/$echange
				fi
				

				if [ -e /var/www/se3/includes/config.inc.php ]; then
					dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
					dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
					dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
					dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
				else
					echo "Fichier de conf inaccessible"
					exit 1
				fi

				acces_partage_public=$(echo "SELECT value FROM params WHERE name='autoriser_partage_public';"|mysql -N -h $dbhost -u $dbuser -p$dbpass $dbname)
				if [ "$acces_partage_public" != "n" ]; then
					#Levée de l'interdiction d'accés à public:
					#setfacl -m g:$1:rwx /var/se3/Docs/public
					#echo "setfacl -x g:$GRP_CLASSE /var/se3/Docs/public"
					setfacl -x g:$GRP_CLASSE /var/se3/Docs/public
				fi
			;;
		esac
	fi
else
	#ERREUR: Pour le moment, je ne fais qu'une et une seule classe.
	echo "USAGE: Passer en paramètre \$1 le nom du dossier de classe"
	echo "       (correctement orthographié;o)."
	echo "       Et en paramètre \$2 'actif' pour autoriser l'accés à $echange"
	echo "       et n'importe quoi d'autre pour désactiver."
fi

echo $statut

