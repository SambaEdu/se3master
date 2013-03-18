#!/bin/bash

## $Id$ ##

##### Script destiné à passer en UTF-8. #####

if [ "$1" = "--help" -o "$1" = "-h" ]
then
        echo "Script de conversion de ISO-8859 vers UTF-8"
        echo "Ce script doit etre lance une seule fois apres la migration en ETCH"
        echo "Usage : Aucune option"
        exit
fi



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

echo "Ce script va transformer vos repertoire /home et /var/se3 "
echo "de ISO-8859 vers UTF-8"
POURSUIVRE

if [ ! -e "/usr/bin/convmv" ]
then
        echo "convmv n'est pas installe, on l'installe"
        apt-get install convmv
fi


echo -e "$COLTXT"
echo -e "Les repertoires a migrer sont:"
echo -e "$COLTXT"
echo -e "/home"
echo -e "/var/se3/"



echo -e "$COLTXT"
echo "Arret du serveur samba..."
/etc/init.d/samba stop
sleep 2
test=$(ps aux | grep smbd | grep -v grep)
if [ ! -z "$test" ]; then
	echo -e "$COLERREUR"
	echo "L'arret du serveur a échoue."
	echo "Il reste au moins un processus samba:"
	echo -e "$COLCMD\c"
	ps aux | grep smbd | grep -v grep

	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous reessayer d'arreter samba? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" = "n" ]; then
		echo -e "$COLERREUR"
		echo "Abandon."
		exit
	fi
else
	REP="OK"
fi

echo -e "$COLTXT"
echo "Migration lancee, cela peut prendre un certain temps"

echo -e "$COLTXT"
echo "Passage de ISO-8859-1 vers UTF-8 du repertoire /home"
/usr/bin/convmv --notest -f iso-8859-15 -t utf-8 -r /home

echo -e "$COLTXT"
echo "Passage de ISO-8859-1 vers UTF-8 du repertoire /var/se3"
/usr/bin/convmv --notest -f iso-8859-15 -t utf-8 -r /var/se3/

echo -e "$COLTXT"
echo "Modification de smb.conf pour passage en UTF-8"
perl -pi -e "s/ISO8859-15/UTF8/" /etc/samba/smb.conf

echo -e "$COLTXT"
echo "Modification des locales systeme pour passage en UTF-8"
echo "fr_FR.UTF-8 UTF-8" > /etc/locale.gen
/usr/sbin/locale-gen

echo -e "$COLTXT"
echo -e "Redémarrage du serveur samba"


echo -e "$COLCMD\c"
/etc/init.d/samba start
test=$(ps aux | grep smbd | grep -v grep)
if [ -z "$test" ]; then
	echo -e "$COLERREUR"
	echo "Le redemarrage du service samba a echoue."

	echo -e "$COLTXT"
	echo "Vous devrez redémarrer manuellement le service par:"
	echo -e "$COLCMD\c"
	echo "   /etc/init.d/samba start"
else
	echo -e "$COLTXT"
	echo "Rédémarrage du service samba réussi."
fi


