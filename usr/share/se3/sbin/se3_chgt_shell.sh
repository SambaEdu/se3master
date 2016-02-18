#!/bin/bash

# Script de changement du shell des utilisateurs
# Stephane Boireau (Animateur TICE sur le Secteur de Bernay/Pont-Audemer (27))
# Le 04/02/2006

#
## $Id$ ##


if [ "$1" == "--help" -o "$1" == "-h" ]
then
	echo "Script de changement du shell des utilisateurs"
	echo "Pas d'option"
	echo "--help cette aide"
	
	exit
fi


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
	exit 0
}

POURSUIVRE()
{
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Peut-on poursuivre? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" != "o" ]; then
		ERREUR "Abandon!"
	fi
}

clear
echo -e "$COLTITRE"
echo "************************"
echo "* SCRIPT DE CHANGEMENT *"
echo "*      DE 'SHELL'      *"
echo "************************"

REPLISTE=""
while [ "$REPLISTE" != "1" -a "$REPLISTE" != "2" -a "$REPLISTE" != "3" ]
do
	echo -e "$COLTXT"
	echo -e "Voulez-vous:"
	echo -e "   (${COLCHOIX}1${COLTXT}) Modifier le shell de tous les utilisateurs."
	echo -e "   (${COLCHOIX}2${COLTXT}) Modifier le shell d'un utilisateur particulier."
	echo -e "   (${COLCHOIX}3${COLTXT}) Modifier le shell des membres d'un groupe particulier."
	echo -e "Votre choix: $COLSAISIE\c"
	read REPLISTE
done

# Question: Quel shell imposer...
echo -e "$COLINFO"
echo -e "Vous devez proposer un shell avec son chemin."
echo -e "Voici quelques exemples de shells:"
echo -e "   /bin/bash"
echo -e "   /bin/true"
echo -e "   /bin/false"

REPONSE=""
while [ "$REPONSE" != "1" ]
do
	echo -e "$COLTXT"
	echo -e "Quel shell souhaitez-vous mettre en place pour ces utilisateurs?"
	echo -e "Shell: [${COLDEFAUT}/bin/bash${COLTXT}] $COLSAISIE\c"
	cd /
	read -e CHOIXSHELL

	if [ -z "$CHOIXSHELL" ]; then
		CHOIXSHELL="/bin/bash"
	fi

	echo -e "$COLTXT"
	echo -e "Vous avez-choisi ${COLINFO}$CHOIXSHELL${COLTXT}"
	echo -e "Souhaitez vous poursuivre (${COLCHOIX}1${COLTXT}) ou préférez-vous corriger (${COLCHOIX}2${COLTXT})? $COLSAISIE\c"
	read REPONSE
done

echo -e "$COLINFO"
echo "Sauvegarde initiale du LDAP."
echo "Le serveur LDAP va être arrêté puis redémarré."
echo -e "$COLTXT"
echo "Appuyez sur ENTREE pour poursuivre..."
read PAUSE

echo -e "$COLCMD\c"
ladate=$(date +"%Y.%m.%d-%H.%M.%S");
tmp=/root/tmp/$ladate
mkdir -p $tmp

ROOTDN=$(cat /etc/ldap/slapd.conf | grep '^rootdn' | tr '\n' ' ' | cut -d'"' -f2)
BASEDN=$(cat /etc/ldap/ldap.conf | grep '^BASE' | tr "\n" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)

echo -e "$COLTXT"
echo "Sauvegarde avec ldapsearch..."
echo -e "$COLCMD\c"
ldapsearch -xLLL -D "$ROOTDN" -w "$(cat /etc/ldap.secret)" > $tmp/ldapsearch_${ladate}.ldif

echo -e "$COLTXT"
echo "Arrêt du serveur LDAP..."
echo -e "$COLCMD\c"
/etc/init.d/slapd stop
sleep 5
if ps aux | grep slapd | grep -v grep > /dev/null ;then
	echo -e "$COLERREUR"
	echo "ERREUR: Le serveur LDAP n'est semble-t-il pas arrêté."
	echo "        Par précaution, le script s'arrête là."
	echo -e "$COLTXT"
	exit
else
	echo -e "$COLTXT"
	echo "Le serveur LDAP est arrêté."
fi

echo -e "$COLTXT"
echo "Sauvegarde avec slapcat..."
echo -e "$COLCMD\c"
# Problème: Avec le LDAP sur Sarge, le /var/lib/ldap peut enfler de façon fantastique.
#           Il se peut alors qu'il n'y ait pas la place dans /root/... pour sauvegarder le LDAP avec tar.
#tar -czf $tmp/var_lib_ldap_${ladate}.tar.gz /var/lib/ldap
slapcat > $tmp/slapcat_${ladate}.ldif

echo -e "$COLTXT"
echo "Redémarrage du serveur LDAP..."
echo -e "$COLCMD\c"
/etc/init.d/slapd start
sleep 5

if ps aux | grep slapd | grep -v grep > /dev/null ;then
	echo -e "$COLTXT"
	echo "Le serveur LDAP est redémarré."
else
	echo -e "$COLERREUR"
	echo "ERREUR: Le serveur LDAP n'est semble-t-il pas redémarré."
	echo "        Par précaution, le script s'arrête là."
	echo "        Vous devez redémarrer le serveur LDAP à la main."
	echo -e "$COLTXT"
	exit
fi

echo -e "$COLINFO"
echo "Le dossier $tmp contient des sauvegardes de l'annuaire au cas où..."

# Modification du shell:
case $REPLISTE in
	1)
		echo -e "$COLTXT"
		echo -e "Vous allez modifier le shell de ${COLERREUR}tous${COLTXT} les utilisateurs dans la branche ou=People."
		echo -e "(excepté 'admin')"
		
		POURSUIVRE

		echo -e "$COLCMD"
		ldapsearch -xLLL -b ou=People,$BASEDN uid | grep "^uid: " | sed -e "s/^uid: //" | while read login
		do
			if [ "$login" != "ldapadm" -a "$login" != "smbadm" -a "$login" != "samba" -a "$login" != "root" -a "$login" != "admin" ]; then
				# ...
				echo "dn: uid=$login,ou=People,$BASEDN" > $tmp/modif_shell_${login}.ldif
				echo "changetype: modify" >> $tmp/modif_shell_${login}.ldif
				echo "replace: loginShell" >> $tmp/modif_shell_${login}.ldif
				echo "loginShell: $CHOIXSHELL" >> $tmp/modif_shell_${login}.ldif
				echo "" >> $tmp/modif_shell_${login}.ldif
				
				echo -e "$COLTXT\c"
				echo "Modification du shell pour $login..."
				echo -e "$COLCMD\c"
				ldapmodify -x -D "$ROOTDN" -w "$(cat /etc/ldap.secret)" -f $tmp/modif_shell_${login}.ldif
			fi
		done
	;;
	2)
		echo -e "$COLTXT"
		echo -e "Pour quel utilisateur souhaitez-vous modifier le shell? $COLSAISIE\c"
		read login

		echo -e "$COLCMD"
		if [ ! -z "$(ldapsearch -xLLL uid=$login | grep 'uid:')" ]; then
			echo "dn: uid=$login,ou=People,$BASEDN" > $tmp/modif_shell_${login}.ldif
			echo "changetype: modify" >> $tmp/modif_shell_${login}.ldif
			echo "replace: loginShell" >> $tmp/modif_shell_${login}.ldif
			echo "loginShell: $CHOIXSHELL" >> $tmp/modif_shell_${login}.ldif
			echo "" >> $tmp/modif_shell_${login}.ldif

			echo -e "$COLTXT\c"
			echo "Modification du shell pour $login..."
			echo -e "$COLCMD\c"
			ldapmodify -x -D "$ROOTDN" -w "$(cat /etc/ldap.secret)" -f $tmp/modif_shell_${login}.ldif
		else
			ERREUR "L'utilisateur $login n'apparait pas dans l'annuaire."
		fi
	;;
	3)
		echo -e "$COLTXT"
		echo -e "Pour quel groupe souhaitez-vous modifier le shell? $COLSAISIE\c"
		read groupe

		echo -e "$COLCMD"
		if [ ! -z "$(ldapsearch -xLLL -b ou=Groups,$BASEDN cn=$groupe | grep 'cn:')" ]; then
			if ldapsearch -xLLL -b ou=Groups,$BASEDN cn=$groupe | grep "objectClass: posixGroup" > /dev/null; then
				ldapsearch -xLLL -b ou=Groups,$BASEDN cn=$groupe | grep "memberUid: " | sed -e "s/^memberUid: //" | while read login
				do
					if [ "$login" != "ldapadm" -a "$login" != "smbadm" -a "$login" != "samba" -a "$login" != "root" -a "$login" != "admin" ]; then
						echo "dn: uid=$login,ou=People,$BASEDN" > $tmp/modif_shell_${login}.ldif
						echo "changetype: modify" >> $tmp/modif_shell_${login}.ldif
						echo "replace: loginShell" >> $tmp/modif_shell_${login}.ldif
						echo "loginShell: $CHOIXSHELL" >> $tmp/modif_shell_${login}.ldif
						echo "" >> $tmp/modif_shell_${login}.ldif
			
						echo -e "$COLTXT\c"
						echo "Modification du shell pour $login..."
						echo -e "$COLCMD\c"
						ldapmodify -x -D "$ROOTDN" -w "$(cat /etc/ldap.secret)" -f $tmp/modif_shell_${login}.ldif
					fi
				done
			else
				ldapsearch -xLLL -b ou=Groups,$BASEDN cn=$groupe | grep "member: " | sed -e "s/^member: uid=//" | cut -d"," -f1 | while read login
				do
					if [ "$login" != "ldapadm" -a "$login" != "smbadm" -a "$login" != "samba" -a "$login" != "root" -a "$login" != "admin" ]; then
						echo "dn: uid=$login,ou=People,$BASEDN" > $tmp/modif_shell_${login}.ldif
						echo "changetype: modify" >> $tmp/modif_shell_${login}.ldif
						echo "replace: loginShell" >> $tmp/modif_shell_${login}.ldif
						echo "loginShell: $CHOIXSHELL" >> $tmp/modif_shell_${login}.ldif
						echo "" >> $tmp/modif_shell_${login}.ldif
			
						echo -e "$COLTXT\c"
						echo "Modification du shell pour $login..."
						echo -e "$COLCMD\c"
						ldapmodify -x -D "$ROOTDN" -w "$(cat /etc/ldap.secret)" -f $tmp/modif_shell_${login}.ldif
					fi
				done
			fi
		else
			ERREUR "Le groupe $groupe n'apparait pas dans l'annuaire."
		fi
	;;
esac

echo -e "$COLTITRE"
echo "Terminé"
echo -e "$COLTXT"

