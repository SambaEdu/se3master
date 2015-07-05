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
		echo "        Vous pouvez aussi mettre en \$2 'alea' pour mettre des mots de passe"
		echo "        aleatoires ou 'semi' pour mettre des mots de passe semi-aleatoires."
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
		echo "ABANDON: L'adresse IP du serveur LDAP n'a pas été identifiée."
		exit
	fi

	if [ -e "/usr/share/se3/sbin/variables_admin_ldap.sh" ]; then
		. /usr/share/se3/sbin/variables_admin_ldap.sh lib > /dev/null
	fi
fi

# Si le variables_admin_ldap.sh n'est pas assez recent
if [ -z "$BASEDN" -o -z "$ROOTDN" -o -z "$PASSDN" ]; then
	# On utilise les parametres locaux... en esperant que le ldap est bien local
	echo "On utilise les paramétres locaux... en espérant que le ldap est bien local"
	BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
	ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
	PASSDN=$(cat /etc/ldap.secret)
fi

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

if [ "$2" = "alea" -o "$2" = "semi" ]; then
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

# fichier csv temporaire destine a l'impression pdf du listing des comptes modifies
temp=/tmp/changement_mdp.csv
if [ ! -e ${temp} ]; then
	touch ${temp}
	chown admin ${temp}
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
# a faire...				echo "$nom;$prenom;$uid;$mdp;$classe;" | tee -a $temp
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
					echo -e "$uid: \tRéinitialisation du mot de passe a $date:\c"
					/usr/share/se3/sbin/userChangePwd.pl $uid $date
					if [ "$?" = "0" ]; then
						echo "OK"
# a faire...						echo "$nom;$prenom;$uid;$mdp;$classe;" | tee -a $temp
					else
						echo "ERREUR"
					fi
				else
					echo "ERREUR (mot de passe non identifié)"
				fi
			fi
		else
			# On met un mot de passe aleatoire ou semi-aleatoire
			if [ "$2" = "alea" ]; then
				mdp=$(/usr/share/se3/sbin/gen_pwd.sh -a)
			else
				mdp=$(/usr/share/se3/sbin/gen_pwd.sh -s)
			fi

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
					echo "$nom;$prenom;$uid;$mdp;$classe" | tee -a $temp
				else
					echo "$groupe;$nom;$prenom;$mail;$uid;ECHEC changement MDP;$classe" | tee -a $dest
				fi
			else
				echo "$groupe;$nom;$prenom;$mail;$uid;ECHEC generation MDP???;$classe  sortie : $mdp" | tee -a $dest
			fi

			chown www-se3 ${temp}  # pour permettre sa suppression

		fi
	done
fi

if [ "$alea" = "y" ]; then
	echo "Un fichier CSV a été généré en"
	echo "   ${dest}"
	echo "Il contient aussi des adresses mail pour un publipostage mail, mais si l'adresse"
	echo "mail renseignée correspond à une authentification sur l'annuaire LDAP pour"
	echo "lequel on vient de changer le mot de passe, cette adresse ne sera pas une bonne"
	echo "solution de communication du changement."
fi

echo "Terminé."

