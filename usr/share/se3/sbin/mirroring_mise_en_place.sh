#!/bin/bash

#/usr/share/se3/sbin/mirroring_mise_en_place.sh
#
## $Id$ ##
#
##### Mise en place du mirroring entre le disk principal et un DEUXIEME DISK #####
# au choix de l'utilisateur
# Franck Molle - Academie de Rouen
# 09/2005 - Ajout de l'arret pour ldap/mysql avant le mirroring
# Stephane Boireau - Academie de Rouen
# 16/01/2006 - Ajout du choix du partitionnement automatique ou non du deuxieme disque.

#Couleurs
COLTITRE="\033[1;35m"	# Rose
COLPARTIE="\033[1;34m"	# Bleu

COLTXT="\033[0;37m"	# Gris
COLCHOIX="\033[1;33m"	# Jaune
COLDEFAUT="\033[0;33m"	# Brun-jaune
COLSAISIE="\033[1;32m"	# Vert

COLCMD="\033[1;37m"	# Blanc

COLIMPORTANT="\033[1;33m"	# Jaune

COLERREUR="\033[1;31m"	# Rouge
COLINFO="\033[0;36m"	# Cyan


if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo -e "$COLINFO"
	echo "Permet de mettre en place un mirroring entre deux disques"
	echo "Script interactif"
	echo "Usage : aucune option"
	echo -e "$COLTXT"
	exit
fi

BLEU="\033[1;34m"
BROWN="\033[0;33m"
ROSE="\033[1;35m"
GRIS="\033[0;37m"
VERT="\033[1;32m"
ROUGE="\033[1;31m"
BLANC="\033[00m"



clear
echo -e "${COLTITRE}
***************************************************************
* Ce script va mettre en place un mirroring a l'aide de rsync *
* entre votre disque principale (/dev/XXX ou /dev/ciss/XXX,   *
* et un 2eme disque de votre choix                            *
*                                                             *
*                                                             *
*${COLIMPORTANT}          /!\ ATTENTION /!\   A CE QUE VOUS FAITES     ${COLTITRE}      *
*  ${COLIMPORTANT}    SI LE DEUXIEME DISQUE CONTIENT DEJA DES DONNEES   ${COLTITRE}     *
*${COLTITRE}                                                             *
*   suggestions, corrections ... : franck.molle@ac-rouen.fr   *
*                                                             *
*          appuyez sur une touche pour continuer              *
***************************************************************"
read OK
echo -e "${COLTXT}"


if [ -n "$(df | grep vol0-lv_home)" ]; then
echo -e "${COLERREUR}Il semble que votre disque soit en LVM "
	echo -e "${COLERREUR}Le script ne peut mettre en place un mirroring avec du LVM pour le moment"
	echo -e "$COLTXT"
	exit 1

fi



# detection de rsync
if [ -e /usr/bin/rsync ]; then
	echo ""
	#echo "Rsync est deja installe"
else
	echo -e "${COLTXT}"
	echo "Rsync est necessaire au mirroring mais ne semble pas "
	echo -e "installe, voulez-vous l'installer maintenant ? (${COLCHOIX}o/n${COLTXT})"
	echo "(une connexion a internet est necessaire)"
	echo -e "Reponse: [${COLDEFAUT}o${COLTXT}] $COLSAISIE\c"
	read REPONSE

	if [ -z "$REPONSE" ]; then
		REPONSE="o"
	fi

	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo "Rsync est necessaire au mirroring mais ne semble pas "
		echo -e "installe, voulez vous l'installer maintenant ? (${COLCHOIX}o/n${COLTXT})"
		echo "(une connexion a internet est necessaire)"
		echo -e "Reponse: $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" = "o" ]; then
		echo -e "$COLTXT"
		echo "Installation de rsync lancee"
		echo -e "$COLCMD\c"
		apt-get update
		apt-get install rsync
	else
		echo -e "$COLTXT"
		echo "Pas d'installation de rsync."
	fi
fi


while [ "$DISK1OK" != "o" ]
do

	DD1DEFAULT=$(cat /etc/fstab | grep -v "^#" | grep /home | tr "\t" " " | cut -d" " -f1 | sed -e "s|[0-9]||g")

	if [ "$(echo $DD1DEFAULT | wc -m)" != "4" ]; then
		DD1DEFAULT="/dev/sda"
	fi

	echo -e "$COLTXT"
	echo -e "Quel est votre premier disque ? [${COLDEFAUT}${DD1DEFAULT}${COLTXT}] ${COLSAISIE}\c"
	read  DISK1
	if [ -z "$DISK1" ]; then
		DISK1=${DD1DEFAULT}
	fi

	echo -e "$COLTXT"
	echo -e "Votre premier disque est ${COLINFO}$DISK1${COLTXT}"
	echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
	read DISK1OK
	if [ -z "$DISK1OK" ]; then
		DISK1OK="o"
	fi
	while [ "$DISK1OK" != "o" -a "$DISK1OK" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
		read DISK1OK
	done
done


#Sauvegarde de la table des partitions du premier disque
echo -e "$COLCMD"
sfdisk -d $DISK1 > /tmp/part

#Detection des partitions disque source
PARTSWAP=`fdisk -l $DISK1 | grep swap | sed -e "s/ .*//"`
PARTROOT=`df | grep "/\$" | sed -e "s/ .*//"`
#PARTHOME=`df | grep "/home" | sed -e "s/ .*//" | sed -e "s/\/dev\///"`
#PARTVARSE3=`df | grep "/var/se3" | sed -e "s/ .*//" | sed -e "s/\/dev\///"`
#TSTVAR=`df | grep "/var"| grep -v /var/se3`

PARTHOME=`df | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | grep " /home$" | sed -e "s/ .*//"`
PARTVARSE3=`df | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | grep " /var/se3$" | sed -e "s/ .*//"`
TSTVAR=`df | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | grep "/var$"| grep -v /var/se3`

echo -e "$COLTXT"
echo "Le script a detecte les partitions suivantes:"
echo ""
echo -e "${COLTXT}Partition SWAP :\t${COLINFO} $PARTSWAP"
echo -e "${COLTXT}Partition Racine :\t${COLINFO} $PARTROOT"
if [ ! -z "$TSTVAR" ]; then
	echo -e "$COLCMD\c"
	#PARTVAR=`df | grep "/var"| grep -v /var/se3 | sed -e "s/ .*//" | sed -e "s/\/dev\///"`
	PARTVAR=`df | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | grep "/var$"| grep -v /var/se3 | sed -e "s/ .*//"`
	echo -e "${COLTXT}Partition /VAR :\t${COLINFO} $PARTVAR"
else
	# echo -e "Pas de Partition /var de detectee "
	PARTVAR="aucune"
fi

echo -e "${COLTXT}Partition /HOME :\t${COLINFO} $PARTHOME"
echo -e "${COLTXT}Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3"

echo -e "$COLTXT"
echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
read DETECTOK
if [ -z "$DETECTOK" ]; then
	DETECTOK="o"
fi
while [ "$DETECTOK" != "o" -a "$DETECTOK" != "n" ]
do
	echo -e "$COLTXT"
	echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
	read DETECTOK
done

if [ "$DETECTOK" = "n" ]; then
	while [ "$PARTOK" != "o" ]
	do
		echo -e "${COLTXT}Quelle est votre partition SWAP ? [${COLDEFAUT}/dev/sda1${COLTXT}] ${COLSAISIE}\c"
		read  PARTSWAP
		if [ -z "$PARTSWAP" ]; then
			PARTSWAP="/dev/sda1"
		fi
		echo -e "${COLTXT}Quelle est votre partition RACINE ? [${COLDEFAUT}/dev/sda2${COLTXT}] ${COLSAISIE}\c"
		read  PARTROOT
		if [ -z "$PARTROOT" ]; then
			PARTROOT="/dev/sda2"
		fi

		echo -e "${COLTXT}Quelle est votre partition /VAR ? [${COLDEFAUT}aucune${COLTXT}] ${COLSAISIE}\c"
		read  PARTVAR
		if [ -z "$PARTVAR" ]; then
			PARTVAR="/dev/sda3"
		fi

		echo -e "${COLTXT}Quelle est votre partition HOME ? [${COLDEFAUT}/dev/sda6${COLTXT}] ${COLSAISIE}\c"
		read  PARTHOME
		if [ -z "$PARTHOME" ]; then
			PARTHOME="/dev/sda6"
		fi

		echo -e "${COLTXT}Quelle est votre partition VAR/SE3 ? [${COLDEFAUT}/dev/sda5${COLTXT}] ${COLSAISIE}\c"
		read  PARTVARSE3
		if [ -z "$PARTVARSE3" ]; then
			PARTVARSE3="/dev/sda5"
		fi

		echo -e "$COLTXT"
		echo "Voici la liste de vos partitions :"
		echo -e "${COLTXT}Partition SWAP :\t${COLINFO} $PARTSWAP"
		echo -e "${COLTXT}Partition Racine :\t${COLINFO} $PARTROOT"

		if [ "$PARTVAR" != "aucune" ]; then
			echo -e "${COLTXT}Partition /VAR :\t${COLINFO} $PARTVAR"
		fi

		echo -e "${COLTXT}Partition /HOME :\t${COLINFO} $PARTHOME"
		echo -e "${COLTXT}Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3"

		echo -e "$COLTXT"
		echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
		read PARTOK
		if [ -z "$PARTOK" ]; then
			PARTOK="o"
		fi

		while [ "$PARTOK" != "o" -a "$PARTOK" != "n" ]
		do
			echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
			read PARTOK
		done


	done
fi

DISK2OK="n"
while [ "$DISK2OK" != "o" ]
do
	DEFAULT_DISK2="/dev/sdb"
	liste_dd=$(sfdisk -g | grep -v "$DISK1:" | cut -d":" -f1)
    test1=${liste_dd[*]}
    test2=($test1)
    j=0
    for i in $test1; do
        j=$[j+1]
    done    
    if [ $j -ge 1 ]; then
		cpt=0
		while [ $cpt -le $j ]
		do
			if sfdisk -s ${test2[$cpt]} >/dev/null 2>&1; then
				DEFAULT_DISK2="${test2[$cpt]}"
				break
			fi

			cpt=$(($cpt+1))
		done
	fi

	echo -e "$COLTXT"
	echo -e "Quel est votre deuxieme disque ? [${COLDEFAUT}${DEFAULT_DISK2}${COLTXT}] $COLSAISIE\c"
	read  DISK2
	if [ -z "$DISK2" ]; then
		DISK2=$DEFAULT_DISK2
	fi

	echo -e "$COLTXT"
	echo -e "Votre deuxieme disk est ${COLINFO}$DISK2"

	echo -e "$COLTXT"
	echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
	read DISK2OK
	if [ -z "$DISK2OK" ]; then
		DISK2OK="o"
	fi
	while [ "$DISK2OK" != "o" -a "$DISK2OK" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
		read DISK2OK
	done
done

if [ "$DISK2" = "$DISK1"  ]; then
	echo -e "${COLERREUR}Erreur !! Vous avez saisi la meme valeur pour le 1er et le 2eme disque."
	echo -e "${COLERREUR}Le script ne peut mettre en place un mirroring sur le meme disque."
	echo -e "$COLTXT"
	exit 1
fi

echo -e "$COLCMD"
DISK2PARTS=`sfdisk -l $DISK2 2>/dev/null`
if [ -z "$DISK2PARTS" ]; then
	echo -e "${COLERREUR}Erreur !! Aucun disque $DISK2 detecte."
	echo -e "${COLERREUR}Vous avez saisi une valeur erronee pour le 2eme disque."
	echo -e "$COLTXT"
	exit 1
fi

#recuperation des noms de partitions du disque 2
PARTSWAP_CIBLE=`echo $PARTSWAP | sed -e "s#$DISK1#$DISK2#"`
PARTROOT_CIBLE=`echo $PARTROOT | sed -e "s#$DISK1#$DISK2#"`
PARTHOME_CIBLE=`echo $PARTHOME | sed -e "s#$DISK1#$DISK2#"`
PARTVARSE3_CIBLE=`echo $PARTVARSE3 | sed -e "s#$DISK1#$DISK2#"`

echo -e "$COLTXT"
echo -e "Voici la liste des (futures) partitions de${COLINFO} $DISK2"
echo -e "${COLTXT}Partition SWAP :\t${COLINFO} $PARTSWAP_CIBLE"
echo -e "${COLTXT}Partition Racine :\t${COLINFO} $PARTROOT_CIBLE"
if [ "$PARTVAR" != "aucune" ]; then
	PARTVAR_CIBLE=`echo $PARTVAR | sed -e "s#$DISK1#$DISK2#"`
	echo -e "${COLTXT}Partition /VAR :\t${COLINFO} $PARTVAR_CIBLE"
fi
echo -e "${COLTXT}Partition /HOME :\t${COLINFO} $PARTHOME_CIBLE"
echo -e "${COLTXT}Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3_CIBLE"

echo -e "$COLTXT"
echo -e "Voulez-vous poursuivre l'installation ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
read REPONSE
if [ -z "$REPONSE" ]; then
	REPONSE="o"
fi
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
	echo -e "$COLTXT"
	echo -e "Voulez-vous poursuivre l'installation ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
	read REPONSE
done
if [ "$REPONSE" = "n" ]; then
	echo -e "${COLERREUR}Action abandonnee, rien n'a ete modifie."
	echo -e "$COLTXT"
	exit 0
fi


#creation des repertoires de travail si besoin
if  [ -e /mirror ]; then
	echo -e "$COLTXT"
	echo -e "Le repertoire /mirror existe deja..."
else
	echo -e "$COLCMD\c"
	mkdir /mirror/
fi
if  [ -e /mirror/part_root ]; then
	echo -e "$COLTXT"
	echo -e "Le repertoire /mirror/part_root existe deja..."
else
	echo -e "$COLCMD\c"
	mkdir /mirror/part_root
fi

if [ "$PARTVAR" != "aucune" ]; then
	if  [ -e /mirror/part_var ]; then
		echo -e "$COLTXT"
		echo -e "Le repertoire /mirror/part_var existe deja..."
	else
		echo -e "$COLCMD\c"
		mkdir /mirror/part_var
	fi
fi
if  [ -e /mirror/part_home ]; then
	echo -e "$COLTXT"
	echo -e "Le repertoire /mirror/part_home existe deja..."
else
	echo -e "$COLCMD\c"
	mkdir /mirror/part_home
fi



if  [ -e /mirror/part_varse3 ]; then
	echo -e "$COLTXT"
	echo -e "Le repertoire /mirror/part_varse3 existe deja..."
else
	echo -e "$COLCMD\c"
	mkdir /mirror/part_varse3
fi

# creation des partitions du 2eme disque
REPONSE=""
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
	echo -e "$COLTXT"
	echo -e "Voulez-vous creer les partitions et formater le disque ${COLINFO}$DISK2${COLTXT} ? (${COLCHOIX}o/n${COLTXT})"
	echo -e "${COLIMPORTANT}Attention, le contenu de $DISK2 sera efface."
	echo -e "$COLTXT"
	echo -e "Reponse: ${COLSAISIE}\c"
	read REPONSE
done

if [ "$REPONSE" = "o" ]; then

	REP=""
	while [ "$REP" != "1" -a  "$REP" != "2" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez-vous effectuer un partitionnement automatique (${COLCHOIX}1${COLTXT})"
		echo -e "ou souhaitez-vous effectuer un partitionnement manuel (${COLCHOIX}2${COLTXT}) ? $COLSAISIE\c"
		read REP
	done

	if [ "$REP" = "1" ]; then
		echo -e "$COLTXT"
		echo -e "Creation des partitions et des systemes de fichiers..."
		echo -e "$COLCMD"
		sfdisk $DISK2 < /tmp/part
		if [ $? != 0 ]; then
			echo -e "${COLIMPORTANT}Erreur lors de la creation des partitions de $DISK2 "
			echo -e "Le script ne peut se poursuivre normalement."
			echo -e "Vos disques ne sont peut etre pas strictement identiques."
			echo -e "$COLTXT"
			echo -e "Vous pouvez executer cfdisk et partitionner manuellement de la meme façon que le 1er disque."
			echo ""
			echo -e "Pour rappel, voici l'ordre dans lequel elles devront apparaitre:"
			echo -e "${COLTXT}Partition SWAP :\t${COLINFO} $PARTSWAP_CIBLE"
			echo -e "${COLTXT}Partition Racine :\t${COLINFO} $PARTROOT_CIBLE"
			if [ "$PARTVAR" != "aucune" ]; then
				echo -e "${COLTXT}Partition /VAR :\t${COLINFO} $PARTVAR_CIBLE"
			fi
			echo -e "${COLTXT}Partition /HOME :\t${COLINFO} $PARTHOME_CIBLE"
			echo -e "${COLTXT}Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3_CIBLE"

			REPONSE=""
			while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
			do
				echo -e "$COLTXT"
				echo -e "Voulez-vous lancer cfdisk maintenant ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
				read REPONSE
			done

			if [ "$REPONSE" = "n" ]; then
				echo -e "${COLERREUR}Operation annulee, rien n'a ete effectue !!!"
				echo -e "$COLTXT"
				exit 1
			fi

			echo -e "$COLCMD"
			/sbin/cfdisk $DISK2
		fi
	else
		echo -e "$COLTXT"
		echo -e "Vous pouvez executer cfdisk et partitionner manuellement de la meme façon que le 1er disque."
		echo ""
		echo -e "Pour rappel, voici l'ordre dans lequel elles devront apparaitre:"
		echo -e "${COLTXT}Partition SWAP :\t${COLINFO} $PARTSWAP_CIBLE"
		echo -e "${COLTXT}Partition Racine :\t${COLINFO} $PARTROOT_CIBLE"
		if [ "$PARTVAR" != "aucune" ]; then
			echo -e "${COLTXT}Partition /VAR :\t${COLINFO} $PARTVAR_CIBLE"
		fi
		echo -e "${COLTXT}Partition /HOME :\t${COLINFO} $PARTHOME_CIBLE"
		echo -e "${COLTXT}Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3_CIBLE"

		REPONSE=""
		while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
		do
			echo -e "$COLTXT"
			echo -e "Voulez-vous lancer cfdisk maintenant ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
			read REPONSE
		done

		if [ "$REPONSE" = "n" ]; then
			echo -e "${COLERREUR}Operation annulee, rien n'a ete effectue !!!"
			echo -e "$COLTXT"
			exit 1
		fi

		echo -e "$COLCMD"
		/sbin/cfdisk $DISK2
	fi

	if [ "$PARTVAR" != "aucune" ]; then
		echo -e "$COLTXT"
		echo -e "Partition /VAR :\t${COLTXT} $PARTVAR"
		echo -e "$COLCMD"
		/sbin/mkswap $PARTSWAP_CIBLE  && /sbin/mke2fs -j $PARTROOT_CIBLE && /sbin/mke2fs -j $PARTVAR_CIBLE && /sbin/mkfs.xfs -f $PARTHOME_CIBLE && /sbin/mkfs.xfs -f $PARTVARSE3_CIBLE
	else
		echo -e "$COLCMD"
		/sbin/mkswap $PARTSWAP_CIBLE  && /sbin/mke2fs -j $PARTROOT_CIBLE && /sbin/mkfs.xfs -f $PARTHOME_CIBLE && /sbin/mkfs.xfs -f /$PARTVARSE3_CIBLE
	fi


	if [ $? != 0 ]; then
		echo -e "${COLERREUR}Erreur lors du formatage des partitions de $DISK2 "
		echo -e "Le script ne peut se poursuivre."
		echo -e "$COLTXT"
		exit 1
	fi

	clear
fi

######### traitement de la crontab #############
echo -e "$COLTXT"
echo "Il va maintenant vous etre propose d'automatiser le mirroring."
grep "/mirror/mirror_rsync.sh" /etc/crontab > /tmp/mirror_crontab_${ladate}.tmp
if [ -s /tmp/mirror_crontab_${ladate}.tmp ]; then
	echo -e "$COLINFO"
	echo "Une ou des taches de mirroring sont deja programmees:"
	echo -e "$COLCMD\c"
	cat /tmp/mirror_crontab_${ladate}.tmp
	echo -e "$COLINFO"
	echo "Veillez a ne pas proposer les memes dates/heures pour la nouvelle tache"
	echo "si vous en programmez une."
fi

echo -e "$COLTXT"
echo -e "Voulez-vous mettre en place le script rsync en crontab ? (${COLCHOIX}o/n${COLTXT})"
echo -e "Il se lancera tous les jours ouvrables a l'heure de votre choix."
echo -e "Il vous sera aussi propose de le lancer plusieurs fois par jour."
echo -e "Reponse: [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
read REPONSE
if [ -z "$REPONSE" ]; then
	REPONSE="o"
fi
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
	echo -e "$COLTXT"
	echo -e "Voulez-vous mettre en place le script rsync en crontab ? (${COLCHOIX}o/n${COLTXT})"
	echo "Il se lancera tous les jours ouvrables a l'heure de votre choix."
	echo "Il vous sera aussi propose de le lancer plusieurs fois par jour."
	echo -e "Reponse: ${COLSAISIE}\c"
	read REPONSE
done
ladate=$(date "+%Y%m%d%H%M%S")

if [ "$REPONSE" = "o" ]; then
#####
	while [ "$CRONAJOUT" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Vous allez devoir preciser a quel moment de la nuit le script s'executera."
		echo -e "Attention, le script coupe ldap et mysql durant son execution, "
		echo -e "il ne doit donc pas etre lance lorsque le serveur est utilise."

		grep "/mirror/mirror_rsync.sh" /etc/crontab > /tmp/mirror_crontab_${ladate}.tmp
		if [ -s /tmp/mirror_crontab_${ladate}.tmp ]; then
			echo -e "$COLINFO"
			echo "Une ou des taches de mirroring sont deja programmees:"
			echo -e "$COLCMD\c"
			cat /tmp/mirror_crontab_${ladate}.tmp
			echo -e "$COLINFO"
			echo "Veillez a ne pas proposer les memes dates/heures pour la nouvelle tache."
		fi

		echo -e "$COLTXT"
		echo -e "Veuillez indiquer les heures et les minutes sous la forme hh:mn [${COLDEFAUT}02:30${COLTXT}] $COLSAISIE\c"
		read HMCRON
		if [ -z "$HMCRON" ]; then
			HMCRON="02:30"
		fi

		echo -e "$COLTXT"
		echo -e "Vous voulez que le script se lance tous les jours a $HMCRON"
		echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
		read CRONOK
		if [ -z "$CRONOK" ]; then
			CRONOK="o"
		fi
		while [ "$CRONOK" != "o" -a "$CRONOK" != "n" ]
		do
			echo -e "$COLTXT"
			echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
			read CRONOK
		done

		if [ "$CRONOK" = "o" ]; then
			MCRON=`echo $HMCRON | cut -d: -f2`
			HCRON=`echo $HMCRON | cut -d: -f1`
			echo "$MCRON $HCRON * * * root /mirror/mirror_rsync.sh" >> /etc/crontab

			echo -e "$COLTXT"
			echo "Modification de la crontab effectuee."
			echo ""
			echo "Vous avez choisi que le script se lance tous les jours a $HMCRON"
			echo -e "Voulez-vous qu'il se lance egalement a un autre moment ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
			read CRONAJOUT
			while [ "$CRONAJOUT" != "o" -a "$CRONAJOUT" != "n" ]
			do
				echo -e "$COLTXT"
				#echo "Vous avez choisi que le script se lance tous les jours ouvrables a $HMCRON"
				echo "Vous avez choisi que le script se lance tous les jours a $HMCRON"
				echo -e "Voulez-vous qu'il se lance egalement a un autre moment ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c"
				read CRONAJOUT
			done
		fi
	done
fi
##########
# Configuration de l'envoi de mail ####
echo -e "$COLTXT"
echo -e "En cas de probleme lors du mirroring des disques,"
echo -e "le script vous previendra par mail."

while [ "$MAIL_ADMINOK" != "o" ]
do
	echo -e "$COLTXT"
	echo "Il vous faut choisir l'adresse mail qui recevra les rapports d'erreur."
	echo "Quelle adresse mail voulez vous utiliser ? ex : admin@votre_domaine"
	echo -e "Adresse email: $COLSAISIE\c"
	read MAIL_ADMIN
	echo -e "$COLTXT"
	echo -e "Vous avez choisi ${COLINFO}${MAIL_ADMIN}${COLTXT} comme adresse email."
	echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
	read MAIL_ADMINOK
	if [ -z "$MAIL_ADMINOK" ]; then
		MAIL_ADMINOK="o"
	fi

done


################# creation des scripts ########################

#------------------ UUID partitions racine ------------------------------------"
PARTROOTUUID=$(blkid | grep $PARTROOT | cut -d" " -f2 | sed s/\"\//g |  sed s"/UUID=//")
PARTROOTUUID_CIBLE=$(blkid | grep $PARTROOT_CIBLE | cut -d" " -f2 | sed s/\"\//g |  sed s"/UUID=//")


# creation du script rsync
echo -e "$COLCMD"

if [ -e /mirror/param_mirror_rsync.sh ]; then
	mv /mirror/param_mirror_rsync.sh /mirror/param_mirror_rsync.sav_$(date "+%Y%m%d-%H%M%S").sh
fi

touch /mirror/param_mirror_rsync.sh
chmod 700 /mirror/param_mirror_rsync.sh
echo "# Fichier de parametres du mirroring

# Disque source
DISK1=$DISK1

# Disque miroir
DISK2=$DISK2

# Adresse mail d'alerte
MAIL_ADMIN=$MAIL_ADMIN

# Liste des partitions sources
PARTROOT=$PARTROOT
PARTROOTUUID=$PARTROOTUUID
PARTVAR=$PARTVAR
PARTHOME=$PARTHOME
PARTVARSE3=$PARTVARSE3

# Liste des partitions cibles
PARTROOT_CIBLE=$PARTROOT_CIBLE
PARTROOTUUID_CIBLE=$PARTROOTUUID_CIBLE
PARTVAR_CIBLE=$PARTVAR_CIBLE
PARTHOME_CIBLE=$PARTHOME_CIBLE
PARTVARSE3_CIBLE=$PARTVARSE3_CIBLE


# Attention: En cas de modification manuelle, relancer le script
#            /usr/share/se3/sbin/genere_mirror_rsync_sh.sh
" > /mirror/param_mirror_rsync.sh

bash /usr/share/se3/sbin/genere_mirror_rsync_sh.sh


###################### fin creation des scripts ###############################

clear
echo -e "$COLINFO"
echo "Script d'installation termine !!!"
echo ""
echo ""
echo "Le script d'installation a genere trois scripts dans /mirror !"
echo "Le script rsync /mirror/mirror_rsync.sh"
echo
echo "Un script /mirror/mount_$DISK2.sh vous permettant de monter les partitions"
echo "de $DISK2 dans les sous repertoires de /mirror afin d'en visualiser le contenu"
echo
echo "Un script /mirror/umount_$DISK2.sh vous permettant de demonter vos partitions"
echo "de $DISK2 dans les sous repertoires de /mirror"

echo -e "$COLTXT"
echo "Il faut maintenant lancer le script rsync de suite "
echo -e "afin d'effectuer une premiere synchronisation (${COLCHOIX}Ok${COLTXT}) $COLSAISIE\c"
read REPONSE

mount -t ext3 $PARTROOT_CIBLE  /mirror/part_root
/usr/bin/rsync -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/sys/* --exclude=/cdrom/* --exclude=/var/*  / /mirror/part_root | tee -a \$FICHIERLOG

echo -e "$COLTITRE"
echo "Installation de grub"
echo -e "$COLCMD"
grub-install --root-directory=/mirror/part_root --no-floppy --recheck $DISK2
#grub-install --root-directory=/mirror/part_root --no-floppy --recheck hd1 
echo -e "$COLINFO Grub installé !!"




mount -t proc none /mirror/part_root/proc
mount -o bind /dev /mirror/part_root/dev
mount -t sysfs sys /mirror/part_root/sys

mount $PARTVAR_CIBLE /mirror/part_root/var/

chroot /mirror/part_root update-grub 
# chroot /mirror/part_root/ /bin/bash
# update-grub
# /usr/sbin/grub-install --recheck --no-floppy $DISK2




sed "s/hd1/hd0/" -i /mirror/part_root/boot/grub/grub.cfg  
sed "s#$PARTROOT_CIBLE#$PARTROOT#" -i /mirror/part_root/boot/grub/grub.cfg

echo "--------------- modification du fstab --------------------------------"
old_uuid_part1=` blkid | grep $DISK1\1 | cut -d" " -f2 | sed s/\"\//g`
old_uuid_part2=` blkid | grep $DISK1\2 | cut -d" " -f2 | sed s/\"\//g`
old_uuid_part3=` blkid | grep $DISK1\3 | cut -d" " -f2 | sed s/\"\//g`
old_uuid_part5=` blkid | grep $DISK1\5 | cut -d" " -f2 | sed s/\"\//g`
old_uuid_part6=` blkid | grep $DISK1\6 | cut -d" " -f2 | sed s/\"\//g`

new_uuid_part1=` blkid | grep $DISK2\1 | cut -d" " -f2 | sed s/\"\//g`
new_uuid_part2=` blkid | grep $DISK2\2 | cut -d" " -f2 | sed s/\"\//g`
new_uuid_part3=` blkid | grep $DISK2\3 | cut -d" " -f2 | sed s/\"\//g`
new_uuid_part5=` blkid | grep $DISK2\5 | cut -d" " -f2 | sed s/\"\//g`
new_uuid_part6=` blkid | grep $DISK2\6 | cut -d" " -f2 | sed s/\"\//g`

#echo "------------------partition 1------------------------------------"
#echo $old_uuid_part1
#echo $new_uuid_part1
#echo "------------------partition 2------------------------------------"
#echo $old_uuid_part2
#echo $new_uuid_part2
#echo "------------------partition 3------------------------------------"
#echo $old_uuid_part3
#echo $new_uuid_part3
#echo "------------------partition 5------------------------------------"
#echo $old_uuid_part5
#echo $new_uuid_part5
#echo "------------------partition 6------------------------------------"
#echo $old_uuid_part6
#echo $new_uuid_part6

sed "s#$old_uuid_part1#$new_uuid_part1#" -i  /mirror/part_root/etc/fstab
sed "s#$old_uuid_part2#$new_uuid_part2#" -i  /mirror/part_root/etc/fstab
sed "s#$old_uuid_part3#$new_uuid_part3#" -i  /mirror/part_root/etc/fstab
sed "s#$old_uuid_part5#$new_uuid_part5#" -i  /mirror/part_root/etc/fstab
sed "s#$old_uuid_part6#$new_uuid_part6#" -i  /mirror/part_root/etc/fstab

umount /mirror/part_root/var/
umount /mirror/part_root/
cd /mirror/
./mirror_rsync.sh
echo -e "$COLTXT"
exit 0
