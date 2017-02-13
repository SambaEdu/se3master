#!/bin/bash

# Script destiné à restaurer une sauvegarde de l'annuaire LDAP.
# Auteur: Stephane Boireau
# Dernière modification: 02/2017 keyser - Ajout correction annuaire post restauration

## $Id$ ##

#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu

COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert

COLCMD="\033[1;37m"     # Blanc

COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

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

POURSUIVRE_OU_CORRIGER()
{
	REPONSE=""
	while [ "$REPONSE" != "1" -a "$REPONSE" != "2" ]
	do
		if [ ! -z "$1" ]; then
			echo -e "$COLTXT"
			echo -e "Peut-on poursuivre (${COLCHOIX}1${COLTXT}) ou voulez-vous corriger (${COLCHOIX}2${COLTXT}) ? [${COLDEFAUT}${1}${COLTXT}] $COLSAISIE\c"
			read REPONSE

			if [ -z "$REPONSE" ]; then
				REPONSE="$1"
			fi
		else
			echo -e "$COLTXT"
			echo -e "Peut-on poursuivre (${COLCHOIX}1${COLTXT}) ou voulez-vous corriger (${COLCHOIX}2${COLTXT}) ? $COLSAISIE\c"
			read REPONSE
		fi
	done
}

. /usr/share/se3/includes/config.inc.sh -lm

BASEDN="$ldap_base_dn"
ADMINRDN="$adminRdn"
ADMINPW="$adminPw"
PEOPLERDN="$peopleRdn"
GROUPSRDN="$groupsRdn"
RIGHTSRDN="$rightsRdn"

PEOPLER=`echo $PEOPLERDN |cut -d = -f 2`
RIGHTSR=`echo $RIGHTSRDN |cut -d = -f 2`
GROUPSR=`echo $GROUPSRDN |cut -d = -f 2`



dossier=/var/se3/save/ldap
mkdir -p ${dossier}

ladate=$(date "+%Y%m%d.%H%M%S")

# Sauvegarde préalable
echo -e "$COLTXT"
echo "Sauvegarde préalable de l'annuaire dans son état actuel..."
echo -e "$COLCMD\c"
ldapsearch -xLLL -D "$ADMINRDN,$BASEDN" -w $ADMINPW > $dossier/svg_${ladate}.ldif

if [ "$?" = "0" ]; then
	echo -e "$COLTXT"
	echo "Sauvegarde réussie."
else
	echo -e "$COLERREUR"
	echo "Echec de la sauvegarde préalable."
	echo "Il n'est pas très raisonnable de poursuivre sans disposer"
	echo "d'une sauvegarde récente."

	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous continuer néanmoins? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" = "n" ]; then
		echo -e "$COLERREUR"
		echo "Abandon."
		echo -e "$COLTXT"
		exit
	else
		REPONSE=""
		while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
		do
			echo -e "$COLTXT"
			echo -e "Etes vous sûr de vouloir continuer? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
			read REPONSE
		done
	fi

	if [ "$REPONSE" = "n" ]; then
		echo -e "$COLERREUR"
		echo "Abandon."
		echo -e "$COLTXT"
		exit
	fi
fi

echo -e "$COLTXT"
echo "Choix de la sauvegarde à restaurer"
cd $dossier
echo -e "Les sauvegardes présentes dans le dossier ${COLINFO}${dossier}${COLTXT} sont:"
echo -e "$COLCMD\c"
ls -lht *.ldif

echo -e "$COLTXT"
echo "Vous pouvez aussi choisir un fichier LDIF situé"
echo "ailleurs dans l'aborescence."

echo -e "$COLTXT"
echo "Quelle sauvegarde souhaitez-vous restaurer?"

REPONSE=""
while [ "$REPONSE" != "1" ]
do
	echo -e "${COLTXT}Votre choix: $COLSAISIE\c"
	read -e SVG

	if [ ! -e "$SVG" ]; then
		echo -e "$COLERREUR"
		echo -e "Le fichier ${COLINFO}${SVG}${COLERREUR} n'existe pas."

		REPONSE=2
	else
		echo -e "$COLTXT"
		echo "Vous avez choisi:"
		echo -e "$COLCMD\c"
		ls -lh $SVG

		POURSUIVRE_OU_CORRIGER
	fi
done

#v=$(df -h | grep /var | grep -v "/var/se3" | sed -e "s/ \{2,\}/ /g" | cut -d" " -f5 | sed -e "s/%//")
#if [ $v -gt 50 ]; then
#
#else
#
#fi

REP=""
while [ -z "$REP" ]
do
	echo -e "$COLTXT"
	echo "Arrêt du serveur d'annuaire..."
	/etc/init.d/slapd stop
	sleep 2
	test=$(ps aux | grep slapd | grep -v grep)
	if [ ! -z "$test" ]; then
		echo -e "$COLERREUR"
		echo "L'arrêt du serveur a échoué."
		echo "Il reste au moins un processus slapd:"
		echo -e "$COLCMD\c"
		ps aux | grep slapd | grep -v grep

		REPONSE=""
		while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
		do
			echo -e "$COLTXT"
			echo -e "Voulez-vous réessayer d'arrêter slapd? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
			read REPONSE
		done

		if [ "$REPONSE" = "n" ]; then
			echo -e "$COLERREUR"
			echo "Abandon."
			exit 1
		fi
	else
		REP="OK"
	fi
done

echo -e "$COLTXT"
echo "Préparation de la nouvelle arborescence /var/lib/ldap"
echo -e "$COLCMD\c"
mv /var/lib/ldap /var/lib/ldap.${ladate}
mkdir /var/lib/ldap
cp /var/lib/ldap.${ladate}/DB_CONFIG /var/lib/ldap/

echo -e "$COLTXT"
echo -e "Restauration de la sauvegarde ${COLINFO}$SVG"
echo -e "$COLCMD\c"
slapadd -c -l $SVG


if [ "$?" = "0" ]; then
	echo -e "$COLTXT"
	echo "La commande a semble-t-il réussi."
	# Droits sur /var/lib/ldap
	chown -R openldap.openldap /var/lib/ldap
	echo -e "$COLTXT"
	echo -e "Redémarrage du serveur d'annuaire LDAP"
	echo -e "$COLCMD\c"
	/etc/init.d/slapd start
	test=$(ps aux | grep slapd | grep -v grep)
	if [ -z "$test" ]; then
		echo -e "$COLERREUR"
		echo "Le redémarrage du service slapd a échoué."

		echo -e "$COLTXT"
		echo "Vous devrez redémarrer manuellement le service par:"
		echo -e "$COLCMD\c"
		echo "   /etc/init.d/slapd start"
	else
		echo -e "$COLTXT"
		echo "Rédémarrage du service slapd réussi."
	fi
	  
	echo -e "$COLTXT"
	echo "Après redémarrage du LDAP, redémarrez si nécessaire les services samba"
	echo "et apache2se."
	
else
	echo -e "$COLERREUR"
	echo "La commande a renvoyé un code d'erreur."
# 	[ -z $(grep "3.1" /etc/debian_version) ] && chown -R openldap.openldap /var/lib/ldap
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous remettre en place l'arborescence précédente? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" = "o" ]; then
		echo -e "$COLTXT"
		echo -e "Rétablissement de la version antérieure..."
		echo -e "$COLCMD\c"
		rm -fr /var/lib/ldap
		mv /var/lib/ldap.${ladate} /var/lib/ldap

		echo -e "$COLTXT"
		echo -e "Redémarrage du serveur d'annuaire LDAP"
		echo -e "$COLCMD\c"
		/etc/init.d/slapd start
		test=$(ps aux | grep slapd | grep -v grep)
		if [ -z "$test" ]; then
			echo -e "$COLERREUR"
			echo "Le redémarrage du service slapd a échoué."

			echo -e "$COLTXT"
			echo "Vous devrez redémarrer manuellement le service par:"
			echo -e "$COLCMD\c"
			echo "   /etc/init.d/slapd start"
		else
			echo -e "$COLTXT"
			echo "Rédémarrage du service slapd réussi."
		fi
		echo -e "$COLTXT"
		echo "Après redémarrage du LDAP, redémarrez si nécessaire les services samba"
		echo "et apache2se."
		exit 1
	else
		echo -e "$COLERREUR"
		echo "L'opération a échoué."
		echo "Le serveur d'annuaire LDAP n'a pas été redémarré."
		echo -e "$COLTXT"
		exit 1
	fi

fi

if [ -e "/usr/share/se3/sbin/corrige_ldap_smb44.sh" ]; then
	echo "Lancement du script de correction de l'annuaire pour samba 4.4"
	sleep 1
	/usr/share/se3/sbin/corrige_ldap_smb44.sh
fi

