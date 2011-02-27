#!/bin/bash

#
## $Id$ ##
#
##### Reinitialisation de mot de passe pour les utilisateurs #####
# Stephane Boireau, Academie de Rouen

if [ "$1" = "--help" -o "$1" = "-h" -o -z "$1" ]
then
		echo "Reinitialisation des mots de passe pour les utilisateurs"
		echo "membres d'un groupe."
		echo "Usage : Passer en parametre \$1 le nom du groupe (posix)."
		echo "        Ex.: sh $0 Profs"
		echo "             ou"
		echo "             sh $0 Classe_2ND3"
		echo "             ou"
		echo "             sh $0 Eleves"
		echo "        Vous pouvez aussi mettre 'alea' en \$2 pour mettre des mots de passe"
		echo "        aleatoires."
		echo "        Dernière alternative: Fournir en parametre \$1 la chaine:"
		echo "             sh $0 csv=CHEMIN/FICHIER.csv"
		echo "        au format:"
		echo "             LOGIN;MDP;"
		echo "        pour imposer les mots de passe d'apres le fichier fourni."
		echo "        Remarque: Le ';' de fin est destine a eviter des blagues avec les fins"
		echo "                  de lignes do$/unix."
		exit
fi

if [ -e "/usr/share/se3/includes/config.inc.sh" ]; then
	#. /usr/share/se3/includes/config.inc.sh -lv
	. /usr/share/se3/includes/config.inc.sh -l

	LDAPIP="$ldap_server"
	BASEDN="$ldap_base_dn"
	ADMINRDN="$adminRdn"
	ADMINPW="$adminPw"

	#PEOPLERDN="$peopleRdn"
	#GROUPSRDN="$groupsRdn"
	#RIGHTSRDN="$rightsRdn"

	ROOTDN=$ADMINRDN,$BASEDN
	PASSDN=$ADMINPW

	#echo "BASEDN=$BASEDN"
	#echo "ROOTDN=$ROOTDN"
	#echo "PASSDN=$PASSDN"
else
	LDAPIP=$(grep "^HOST" /etc/ldap/ldap.conf|cut -d" " -f2)
	if [ -z "$LDAPIP" ]; then
		echo "ABANDON: L'adresse IP du serveur LDAP n'a pas ete identifiee."
		exit
	fi

	if [ -e "/usr/share/se3/sbin/variables_admin_ldap.sh" ]; then
		. /usr/share/se3/sbin/variables_admin_ldap.sh lib > /dev/null
	fi
fi

# Si le variables_admin_ldap.sh n'est pas assez recent
if [ -z "$BASEDN" -o -z "$ROOTDN" -o -z "$PASSDN" ]; then
	# On utilise les parametres locaux... en esperant que le ldap est bien local
	echo "On utilise les parametres locaux... en esperant que le ldap est bien local"
	BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
	ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
	PASSDN=$(cat /etc/ldap.secret)
fi

GEN_MDP() {
	nbcarmdp=8
	caract="0 1 2 3 4 5 6 7 8 9 A Z E R T Y U I O P Q S D F G H J K L M W X C V B N a z e r t y u i o p q s d f g h j k l m w x c v b n"

	IFS=" "
	liste=($(echo $caract))
	nbcaract=${#liste[*]}
	password=""
	cpt=1
	while [ $cpt -le $nbcarmdp ]
	do
		#index=$(echo "$RANDOM*$nbcaract/32767" | bc)
		index=$(($RANDOM*$nbcaract/32767))
		ajout=${liste[$index]}
		password=${password}${ajout}
		cpt=$(($cpt+1))
	done
	echo $password
}

echo "Sauvegarde de l'annuaire..."
#echo "ldapsearch -xLLL -D $ROOTDN -w $PASSDN > /var/se3/save/ldap_$(date +%Y%m%d%H%M%S).ldif"
ldapsearch -xLLL -D $ROOTDN -w $PASSDN > /var/se3/save/ldap_$(date +%Y%m%d%H%M%S).ldif

if [ "$?" != "0" ]; then
	echo "ERREUR lors de la sauvegarde de l'annuaire."
	echo "Abandon."
	exit
fi

groupe=$1

fichcsv=""

if [ "$2" = "alea" ]; then
	alea=y
	dest=/home/admin/Bureau/changement_mdp_${1}_$(date +%Y%m%d%H%M%S).csv
	touch ${dest}
	chown admin ${dest}
else
	alea=n
	if [ "${1:0:4}" = "csv=" -a -e "${1:4}" ]; then
		fichcsv=${1:4}
	fi
fi

if [ -n "$fichcsv" ]; then
	while read ligne
	do
		uid=$(echo "$ligne"|cut -d";" -f1)
		pass=$(echo "$ligne"|cut -d";" -f2)
		if [ -n "$uid" -a -n "$pass" ]; then
			t=$(ldapsearch -xLLL -b ou=People,$BASEDN uid=$uid)
			if [ -z "$t" ]; then
				echo "Le login $uid n'existe pas dans la branche People de l'annuaire."
			else
				echo -e "$uid: \tModificatiation du mot de passe en $pass"
				/usr/share/se3/sbin/userChangePwd.pl $uid $pass
			fi
		fi
	done < $fichcsv

	if echo "$*" | grep -q nettoyage; then
		rm -f $fichcsv
	fi
else
	ldapsearch -xLLL cn=$groupe | grep memberUid | while read A
	do
		uid=$(echo "$A" | cut -d" " -f2)
		if [ "$alea" = "n" ]; then
			# On fait une reinitialisation a la date de naissance le mot de passe:
			date=$(ldapsearch -xLLL uid=$uid | grep "^gecos:" | cut -d"," -f2)
			if smbclient -L 127.0.0.1 -U $uid%$date > /dev/null 2>&1; then
				echo -e "$uid: \tLa date de naissance est le mot de passe."
			else
				tmp_test=$(echo "$date" | sed -e "s/[0-9]//g")
				if [ -z "${tmp_test}" -a ! -z "$date" ]; then
					echo -e "$uid: \tReinitialisation du mot de passe a $date:\c"
					/usr/share/se3/sbin/userChangePwd.pl $uid $date
					if [ "$?" = "0" ]; then
						echo "OK"
					else
						echo "ERREUR"
					fi
				else
					echo "ERREUR (mot de passe non identifie)"
				fi
			fi
		else
			# On met un mot de passe aleatoire
			mdp=$(GEN_MDP)
	
			mail=$(ldapsearch -xLLL uid=$uid mail | grep "^mail:" | sed -e "s/^mail: //")
			nom=$(ldapsearch -xLLL uid=$uid sn | grep "^sn:" | sed -e "s/^sn: //")
			prenom=$(ldapsearch -xLLL uid=$uid givenName | grep "^givenName:" | sed -e "s/^givenName: //")
	
			classe=""
			if [ "$groupe" = "Eleves" ]; then
				classe=$(ldapsearch -xLLL "(&(memberUid=$uid)(cn=Classe_*))" cn | grep "^cn:" | sed -e "s/^cn: //")
			fi
	
			if [ -n "$mdp" ]; then
	
				/usr/share/se3/sbin/userChangePwd.pl $uid $mdp
				if [ "$?" = "0" ]; then
					echo "$groupe;$nom;$prenom;$mail;$uid;$mdp;$classe" | tee -a $dest
				else
					echo "$groupe;$nom;$prenom;$mail;$uid;ECHEC changement MDP;$classe" | tee -a $dest
				fi
			else
				echo "$groupe;$nom;$prenom;$mail;$uid;ECHEC generation MDP???;$classe" | tee -a $dest
			fi
		fi
	done
fi

if [ "$alea" = "y" ]; then
	echo "Un fichier CSV a ete genere en"
	echo "   ${dest}"
	echo "Il contient aussi des adresses mail pour un publipostage mail, mais si l'adresse"
	echo "mail renseignee correspond a une authentification sur l'annuaire LDAP pour"
	echo "lequel on vient de changer le mot de passe, cette adresse ne sera pas une bonne"
	echo "solution de communication du changement."
fi

echo "Termine."

