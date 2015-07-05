#!/bin/bash

## $Id$ ##


# génère un mot de passe (semi-)aléatoire...
# Christian Westphal - licence WTFPL


# texte d'aide - 'vais quand même pas faire une page de man pour ce bidule !
if [ "$1" == "--help" -o "$1" == "-h" ]
then
	echo "script de génération de mot de passe (semi)aléatoire"
	echo "options : -a pour un mot de passe aléatoire"
        echo "          -s pour un mot de passe semi-aléatoire (valeur par défaut)"
	echo "          --help ou -h pour cette aide super-utile"
	
	exit 0
fi

case "$1" in 
	"-a")
		# mot de passe aleatoire 8 caractères
		# pas de vérification de complexité, faites confiance au hasard

		pass=""
		Car="0123456789azertyuiopqsdfghjklmwxcvbn"

		while [ "${n:=1}" -le "8" ]
		do	pass="$pass${Car:$(($RANDOM%${#Car})):1}"
  			let n+=1
		done

		echo "$pass"
		;;

	"-s" | "")
		# mot de passe semi aleatoire (par défaut)

		pass=""
		C="zrtpqsdfghjklmwxcvbn"
		V="aeyuio"
		N="0123456789"

		while [ "$pass" == "" ]
		do 	pass="$pass${C:$(($RANDOM%${#C})):1}"
			pass="$pass${V:$(($RANDOM%${#V})):1}"
			pass="$pass${C:$(($RANDOM%${#C})):1}"
			pass="$pass${V:$(($RANDOM%${#V})):1}"

			# test politiquement correct
			case $pass in bite|nazi|zizi|pute|zobi|caca|pipi|pede )
				pass="" ;;
			esac
		done

		pass="$pass${N:$(($RANDOM%${#N})):1}"
		pass="$pass${N:$(($RANDOM%${#N})):1}"

		echo "$pass"
		;;

	*)
		# parametre incorrect
		echo "option incorrecte"
		echo "utilisez -a ou -s (-s par défaut)"
		echo "RTFM : --help ou -h"

		exit 10

esac

