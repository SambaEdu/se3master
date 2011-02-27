#!/bin/bash
#
##### Script de g�n�ration d'un CSV des membres d'un groupe #####
#
# Auteur: St�phane Boireau (A.S. - Relais de Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# /usr/share/se3/sbin/export_csv_groupe.sh
# Derni�re modification: 23/06/2007

if [ "$1" = "--help" -o "$1" = "-h" -o -z "$1" ]; then
	echo "Script destin� � effectuer un export CSV des membres d'un groupe"
	echo "en fournissant les champs suivants:"
	echo "   Login;Nom complet;Nom;Prenom;Naissance;Sexe;Email"
	echo ""
	echo "Usage : Passer le nom du groupe en param�tre \$1"
	echo "        La chaine __CLASSES__ est aussi accept�e"
	echo "        et g�n�re un CSV par classe."
	echo "        Les fichiers sont g�n�r�s dans un sous-dossier"
	echo "        du Home de l'utilisateur admin."
	exit
fi

if [ ! -e "/home/admin" ]; then
	echo "ERREUR:"
	echo "   Le compte admin ne s'est jamais connect�."
	echo "   Son dossier personnel n'existe pas encore."
	echo "   Connectez-vous une fois en admin!"
	exit
fi

BASEDN=$(cat /etc/ldap/ldap.conf | grep '^BASE' | tr "\n" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
ladate=$(date +"%Y.%m.%d-%H.%M.%S");

EXPORT_CSV(){
	if [ -z "$2" ]; then
		destination="/home/admin/Docs/export_csv_groupe_${1}_${ladate}"
	else
		destination="/home/admin/Docs/export_csv_groupe_${2}_${ladate}"
	fi
	mkdir -p "${destination}"

	echo "Login;Nom complet;Nom;Prenom;Naissance;Sexe;Email" > "${destination}/$1.csv"
	ldapsearch -xLLL cn=$1 | grep "^memberUid: " | sed -e "s/memberUid: //" | while read uid
	do
		nom_complet=$(ldapsearch -xLLL uid=$uid cn | grep "^cn: " | sed -e "s/^cn: //")
		lnom_complet=$(($(echo "$nom_complet" | wc -m)-1))

		nom=$(ldapsearch -xLLL uid=$uid sn | grep "^sn: " | sed -e "s/^sn: //")
		lnom=$(($(echo "$nom" | wc -m)-1))

		ldiff=$(($lnom_complet-$lnom))
		nb=$(($ldiff-1))

		prenom=${nom_complet:0:$nb}

		mail=$(ldapsearch -xLLL uid=$uid mail | grep "^mail: " | sed -e "s/^mail: //")

		gecos=$(ldapsearch -xLLL uid=$uid gecos | grep "^gecos: " | sed -e "s/^gecos: //")
		naissance=$(echo "$gecos" | cut -d"," -f2)
		sexe=$(echo "$gecos" | cut -d"," -f3)

		echo "$uid;$nom_complet;$nom;$prenom;$naissance;$sexe;$mail" | tee -a "${destination}/$1.csv"
	done
}

if [ "$1" = "__CLASSES__" ]; then
	ldapsearch -xLLL cn=Classe_* cn | grep "^cn: " | sed -e "s/^cn: //" | while read classe
	do
		EXPORT_CSV $classe 'CLASSES'
	done
	chown -R admin "/home/admin/Docs/export_csv_groupe_CLASSES_${ladate}"
else
	test=$(ldapsearch -xLLL -b ou=Groups,$BASEDN cn=$1)
	if [ -z "$test" ]; then
		echo "Le groupe propos� n'existe pas dans l'annuaire."
	else
		EXPORT_CSV $1
		chown -R admin "/home/admin/Docs/export_csv_groupe_${1}_${ladate}"
	fi
fi

echo "Termin�."

