#!/bin/bash
#
##### Script de nettoyage des 'profile' (XP) #####
#
# Auteur : Stephane Boireau (Bernay/Pont-Audemer (27))
#
## $Id ##
#
# Dernière modif: 12/09/2006

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script de nettoyage/suppression des 'profile' (XP)."
	echo "Usage : Aucune option"
	echo "        Il suffit de répondre aux questions."
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
echo "***********************"
echo "* SCRIPT DE NETTOYAGE *"
echo "*    DES 'PROFILE'    *"
echo "***********************"

REPONSE=""
while [ "$REPONSE" != "1" -a "$REPONSE" != "2" -a "$REPONSE" != "3" ]
do
	echo -e "$COLTXT"
	echo -e "Voulez-vous:"
	echo -e "   (${COLCHOIX}1${COLTXT}) Nettoyer tous les /home/<user>/profile/"
	echo -e "   (${COLCHOIX}2${COLTXT}) Nettoyer un /home/<user>/profile/ particulier."
	echo -e "   (${COLCHOIX}3${COLTXT}) Nettoyer les home/<user>/profile/ pour un groupe particulier."
	echo -e "Votre choix: $COLSAISIE\c"
	read REPONSE
done

case $REPONSE in
	1)
		echo -e "$COLTXT"
		echo -e "Vous allez nettoyer ${COLERREUR}tous${COLTXT} les /home/<user>/profile/"
		echo "Cette opération est irréversible."
		POURSUIVRE

		ls /home/ | while read A
		do
                        if [ -e "/home/$A/profile" ]; then
				if echo "$A" | egrep "(admin|skeluser|templates|netlogon)" > /dev/null; then
					echo -e "$COLTXT"
					echo -e "Voulez-vous vider /home/$A/profile/* ? (o/n) \c"
					read REP < /dev/tty
					if [ "$REP" = "o" ]; then
						echo -e "{$COLCMD}rm -fr /home/$A/profile/*"
						rm -fr /home/$A/profile/*
					fi
				else
					echo -e "{$COLCMD}rm -fr /home/$A/profile/*"
					rm -fr /home/$A/profile/*
				fi
			fi
		done
	;;
	2)
		echo -e "$COLTXT"
		echo -e "Nom du compte à nettoyer: $COLSAISIE\c"
		read COMPTE

		if [ -e "/home/$COMPTE" ]; then
			echo -e "$COLTXT"
			echo "Vous allez nettoyer /home/$COMPTE/profile/"
			POURSUIVRE

                        if [ -e "/home/$COMPTE/profile" ]; then
				echo -e "${COLCMD}rm -fr /home/$COMPTE/profile/*"
				rm -fr /home/$COMPTE/profile/*
			fi
		else
			echo -e "$COLERREUR"
			echo "Le compte proposé n'existe pas ou ne s'est pas encore logué."
		fi
	;;
	3)
		echo -e "$COLTXT"
		echo -e "Nom du groupe à nettoyer: $COLSAISIE\c"
		read GROUPE

		echo -e "$COLCMD"
		ldapsearch -x -LLL cn=$GROUPE | grep memberUid | while read A
		do
			COMPTE=$(echo "$A" | cut -d":" -f2 | sed -e "s/ //g")
			#if [ -e "/home/$COMPTE" ]; then
                        if [ -e "/home/$COMPTE/profile" ]; then
				if echo "$COMPTE" | egrep "(admin|skeluser|templates|netlogon)" > /dev/null; then
					echo -e "$COLTXT"
					echo -e "Voulez-vous vider /home/$COMPTE/profile/* ? (o/n) \c"
					read REP < /dev/tty
					if [ "$REP" = "o" ]; then
						echo -e "{$COLCMD}rm -fr /home/$COMPTE/profile/*"
						rm -fr /home/$COMPTE/profile/*
					fi
				else
					echo -e "${COLCMD}rm -fr /home/$COMPTE/profile/*"
					rm -fr /home/$COMPTE/profile/*
				fi
			fi
		done
	;;
esac

echo -e "$COLTITRE"
echo "Terminé"
echo -e "$COLTXT"

