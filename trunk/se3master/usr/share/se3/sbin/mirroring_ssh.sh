#!/bin/bash

#/usr/share/se3/sbin/miroring_ssh.sh
##/usr/share/se3/sbin/miroring_mise_en_place.sh

#
## $Id$ ##
#
##### Mise en place du mirroring entre le disk principal et un DEUXIEME DISK #####
# au choix de l'utilisateur
# modestement ecrit par Franck Molle, dernieres modifs 08/2004

#Script humblement modifié par Stéphane Boireau pour permettre le rsync vers une machine distante.
#Modifs: 04/04/2005

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Permet de mettre en place un mirroring entre le disque principal et un deuxième disque"
	echo "Script interactif"
	echo "Usage : aucune option"
	exit
fi	


#Chemin des scripts:
chemin="."
#Passer par la suite à /usr/share/se3/sbin/



#================================================
#Clé publique du compte root local:
clepublique="/var/remote_adm/.ssh/id_rsa.pub"
#Cette clé sans PASSPHRASE va permettre l'éexécution automatique en crontab
#(i.e. sans intervention humaine pour une saisie de mot de passe) du script de mirroring.

#INUTILE: On en crée une autre!
#================================================



#Dossier temporaire:
ladate=$(date +"%Y.%m.%d-%H.%M.%S");
tmp="/tmp/tmp.${ladate}"
mkdir -p "$tmp"



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

#COLMDP=""



ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$1"
	echo -e "$COLTXT"
	read PAUSE
	if [ ! -z "$email" ]; then
		echo "ERREUR" > "$tmp/erreur.txt"
		echo -e "$1" >> "$tmp/erreur.txt"
		mail $email -s"[Mise en place Mirroring SSH] ERREUR" < "$tmp/erreur.txt"

		#==============================================
		#MODIF temporaire:
		cp "$tmp/erreur.txt" /root/erreur_${ladate}.txt
		#==============================================

		rm -f "$tmp/erreur.txt"
	fi
	#A décommenter après la phase de debug:
	#rm -fr $tmp
	echo -e "$COLTXT"
	exit 0
}

POURSUIVRE()
{
	if [ -z "$1" ]; then
		QUESTION="Peut-on poursuivre"
	else
		QUESTION="$1"
	fi

	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "$QUESTION? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" != "o" ]; then
		ERREUR "Abandon!"
	fi
}


AFFICHHD()
{
	echo -e "$COLTXT"
	echo "Voici la liste des disques trouvés sur votre machine:"
	echo -e "$COLCMD"
	TEMOIN=""
	if /bin/dmesg | grep hd | grep drive | grep -v driver | grep -v ROM; then
		TEMOIN="OK"
	fi

	#/bin/dmesg | grep sd | grep drive | grep -v driver | grep -v ROM
	if /bin/dmesg | grep sd | grep SCSI | grep -v ROM; then
		TEMOIN="OK"
	fi

	if [ "$TEMOIN" != "OK" ]; then
		echo -e "${COLINFO}Les méthodes précédentes de détection n'ont pas fonctionné."
		echo "Deux autres méthodes vont être tentées."
		echo "Si elles échouent, il vous faudra connaitre"
		echo -e "l'identifiant (hda, hdb,...) du disque pour poursuivre.${COLCMD}"
		#Sur les IBM Thinkpad, les commandes précédentes ne donnent rien alors que /dev/hda est bien présent.
		#/bin/dmesg | grep dev | grep host | grep bus | grep target | grep lun | cut -d ":" -f 1 | sed -e "s/ //g" | sed -e "s!ide/host0/bus0/target0/lun0!hda!g" | sed -e "s!ide/host0/bus0/target1/lun0!hdb!g" | sed -e "s!ide/host0/bus1/target0/lun0!hdc!g" | sed -e "s!ide/host0/bus1/target1/lun0!hdd!g"
		#if /bin/dmesg | grep dev | grep host | grep bus | grep target | grep lun | cut -d ":" -f 1 | sed -e "s/ //g" | sed -e "s!ide/host0/bus0/target0/lun0!hda!g" | sed -e "s!ide/host0/bus0/target1/lun0!hdb!g" | sed -e "s!ide/host0/bus1/target0/lun0!hdc!g" | sed -e "s!ide/host0/bus1/target1/lun0!hdd!g"; then
		if /bin/dmesg | grep dev | grep host | grep bus | grep target | grep lun > /dev/null; then
			/bin/dmesg | grep dev | grep host | grep bus | grep target | grep lun | cut -d ":" -f 1 | sed -e "s/ //g" | sed -e "s!ide/host0/bus0/target0/lun0!hda!g" | sed -e "s!ide/host0/bus0/target1/lun0!hdb!g" | sed -e "s!ide/host0/bus1/target0/lun0!hdc!g" | sed -e "s!ide/host0/bus1/target1/lun0!hdd!g"
			TEMOIN="OK"
		fi
		#Une alternative sera: ls /dev/hd*
	fi

	if [ "$TEMOIN" != "OK" ]; then
		echo ""
		ls /dev/ | egrep "(hd|sd)" | grep -v "[0-9]" 2>/dev/null |while read A
		do
			if fdisk -l /dev/$A | grep Blocks > /dev/null; then
				echo $A
				echo "OK" > /tmp/TEMOIN
			fi
		done
		if [ -e "/tmp/TEMOIN" ]; then
			TEMOIN=$(cat /tmp/TEMOIN)
			rm -f /tmp/TEMOIN
			echo -e "$COLINFO"
			echo "Un message éventuel indiquant:"
			echo -e "${COLERREUR}Disk /dev/XdY doesn't contain a valid partition table"
			echo -e "${COLINFO}signifie seulement que le périphérique /dev/XdY ne doit pas être un disque dur."
		fi
	fi

	if [ "$TEMOIN" != "OK" ]; then
		echo -e "$COLCMD"
		if ls /dev/hd* | grep "[0-9]" > /dev/null; then
			ls /dev/hd* | grep "[0-9]" | sed -e "s!/dev/!!g" | sed -e "s/[0-9]*//g"
		else
			echo -e "${COLINFO}Le(s) disque(s) dur(s) n'a/ont pas été identifié(s) par mon script.\nCela ne vous empêche pas de poursuivre,\nmais il faut alors connaitre le périphérique...${COLTXT}"
		fi
	fi
}



clear
echo -e "${COLTITRE}"
echo "******************************"
echo "* Mise en place du mirroring *"
echo "******************************"

echo -e "${COLINFO}"
echo "****************************************************************"
echo "* Ce script va mettre en place un mirroring a l'aide de rsync  *"
echo "* entre HDA / SDA / etc ... et un 2eme disque de votre choix.  *"
#==========================
echo "* Le 2è disque dur pourra se trouver sur une machine distante. *"
#==========================
echo "*                                                              *"
echo -e "*${COLERREUR}           /!\ ATTENTION /!\   A CE QUE VOUS FAITES    ${COLINFO}       *"
echo -e "*  ${COLERREUR}     SI LE DEUXIEME DISQUE CONTIENT DEJA DES DONNEES  ${COLINFO}      *"
echo "*                                                              *"
echo "*   Suggestions, corrections,... : franck.molle@ac-rouen.fr    *"
echo "*                                                              *"
echo "*             Appuyez sur ENTREE pour continuer                *"
echo "****************************************************************"
echo -e "${COLTXT}"
read OK

echo -e "$COLPARTIE"
echo "*********************"
echo "* Présence de rsync *"
echo "*********************"

# Détection de rsync et installation si nécessaire:
if [ -e /usr/bin/rsync ]; then
        echo -e "${COLTXT}"
	#echo ""
        echo "Rsync est déjà installé."
else
        REPONSE=""
        while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
        do
		echo -e "${COLTXT}"
                echo "Rsync est nécessaire au mirroring, mais ne semble pas installé."
                echo -e "Voulez-vous l'installer maintenant?"
                echo "(une connexion à internet est nécessaire)"
                echo -e "Réponse: (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
                read REPONSE

		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done
        if [ "$REPONSE" = "o" ]; then
	        echo -e "${COLTXT}"
                echo "Installation de rsync lancée:"
	        echo -e "${COLCMD}"
                apt-get update
                apt-get install rsync
        else
	        echo -e "${COLTXT}"
                echo "Pas d'installation de rsync."
        fi
fi

echo -e "$COLPARTIE"
echo "******************************"
echo "* Choix du disque dur source *"
echo "******************************"

AFFICHHD

# Choix du premier disque (le disque source):
while [ "$DISK1OK" != "o" ]
do
        echo -e "${COLTXT}"
	echo -e "Quel est votre premier disque? [${COLDEFAUT}hda${COLTXT}] ${COLSAISIE}\c"
	read DISK1
	if [ -z "$DISK1" ]; then
		DISK1="hda"
	fi

	DISK1OK=""
	while [ "$DISK1OK" != "o" -a "$DISK1OK" != "n" ]
	do
		if [ ! -z "$(fdisk -l /dev/$DISK1)" ]; then
			echo -e "${COLTXT}"
			echo -e "Votre premier disque est${COLINFO} $DISK1 ${COLTXT}"
			echo -e "Est-ce correct? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
			read DISK1OK
			if [ -z "$DISK1OK" ]; then
				DISK1OK="o"
			fi
		else
			echo -e "${COLERREUR}"
			echo "Aucune partition n'a été trouvée sur $DISK1"
			echo "Vous avez dû vous tromper de disque."
			echo ""
			echo -e "${COLTXT}Tapez ENTREE pour corriger."
			read PAUSE
			DISK1OK="n"
		fi
	done
done


#Sauvegarde de la table des partitions du premier disque:
echo -e "${COLCMD}"
sfdisk -d /dev/$DISK1 > /tmp/part

#Détection des partitions du disque source:
PARTSWAP=`fdisk -l /dev/$DISK1 | grep swap | sed -e "s/ .*//" | sed -e "s/\/dev\///" `
PARTROOT=`df | grep "/\$" | sed -e "s/ .*//" | sed -e "s/\/dev\///" `
PARTHOME=`df | grep "/home" | sed -e "s/ .*//" | sed -e "s/\/dev\///"`
PARTVARSE3=`df | grep "/var/se3" | sed -e "s/ .*//" | sed -e "s/\/dev\///"`

TSTVAR=`df | grep "/var"| grep -v /var/se3`

echo -e "${COLTXT}"
echo "Le script a détecté les partitions suivantes:"
echo ""
echo -e "${COLTXT}Partition SWAP :\t${COLINFO} $PARTSWAP"
echo -e "${COLTXT}Partition Racine :\t${COLINFO} $PARTROOT"
if [ ! -z "$TSTVAR" ]; then
	echo -e "${COLCMD}\c"
	PARTVAR=`df | grep "/var"| grep -v /var/se3 | sed -e "s/ .*//" | sed -e "s/\/dev\///"`
	echo -e "${COLTXT}Partition /VAR :\t${COLINFO} $PARTVAR"
else
	# echo -e "Pas de Partition /var de detectée "
	PARTVAR="aucune"
fi
echo -e "${COLTXT}Partition /HOME :\t${COLINFO} $PARTHOME"
echo -e "${COLTXT}Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3"

DETECTOK=""
while [ "$DETECTOK" != "o" -a "$DETECTOK" != "n" ]
do
	echo -e "${COLTXT}"
	echo -e "Est-ce correct? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
	read DETECTOK
	if [ -z "$DETECTOK" ]; then
		DETECTOK="o"
	fi
done

if [ "$DETECTOK" = "n" ]; then

        while [ "$PARTOK" != "o" ]
	do
		#Saisie des partitions:
		echo -e "$COLTXT"
                echo -e "Quelle est votre partition SWAP ? [${COLDEFAUT}hda1${COLTXT}] ${COLSAISIE}\c"
                read  PARTSWAP
                if [ -z "$PARTSWAP" ]; then
                        PARTSWAP=hda1
                fi

		echo -e "$COLTXT"
                echo -e "Quelle est votre partition RACINE ? [${COLDEFAUT}hda2${COLTXT}] ${COLSAISIE}\c"
                read  PARTROOT
                if [ -z "$PARTROOT" ]; then
                        PARTROOT=hda2
                fi

		echo -e "$COLTXT"
		echo -e "Quelle est votre partition /VAR ? [${COLDEFAUT}aucune${COLTXT}] ${COLSAISIE}\c"
                read  PARTVAR
                if [ -z "$PARTVAR" ]; then
                        PARTVAR=aucune
                fi

		echo -e "$COLTXT"
                echo -e "Quelle est votre partition HOME ? [${COLDEFAUT}hda3${COLTXT}] ${COLSAISIE}\c"
                read  PARTHOME
                if [ -z "$PARTHOME" ]; then
                        PARTHOME=hda3
                fi

		echo -e "$COLTXT"
                echo -e "Quelle est votre partition VAR/SE3 ? [${COLDEFAUT}hda4${COLTXT}] ${COLSAISIE}\c"
                read  PARTVARSE3
                if [ -z "$PARTVARSE3" ]; then
                        PARTVARSE3=hda4
                fi

		#Récapitulatif des partitions:
		echo -e "$COLTXT"
		echo "Voici la liste de vos partitions:"
		echo -e "Partition SWAP :\t${COLINFO} $PARTSWAP ${COLTXT}"
		echo -e "Partition Racine :\t${COLINFO} $PARTROOT ${COLTXT}"
		if [ "$PARTVAR" != "aucune" ]; then
			echo -e "Partition /VAR :\t${COLINFO} $PARTVAR ${COLTXT}"
		fi
		echo -e "Partition /HOME :\t${COLINFO} $PARTHOME ${COLTXT}"
		echo -e "Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3 ${COLTXT}"

		#Confirmation ou correction:
		PARTOK=""
                while [ "$PARTOK" != "o" -a "$PARTOK" != "n" ]
                do
			echo -e "$COLTXT"
			echo -e "Est-ce correct? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
                        read PARTOK

			if [ -z "$PARTOK" ]; then
				PARTOK="o"
			fi
                done
        done
fi

echo -e "$COLPARTIE"
echo "***********************************"
echo "* Choix du disque dur destination *"
echo "***********************************"

echo -e "$COLINFO"
echo "Le disque dur peut être:"
echo "     - un disque dur local (dans le serveur)"
echo "     - un disque dur distant (dans une autre machine)"
echo ""
echo "Dans le cas où vous optez pour un disque dur distant,"
echo "voici quelques indications:"
echo "     - La machine distante doit disposer de deux disques durs:"
echo "          . Un pour le système distant."
echo "          . Un autre pour accueillir le miroir du disque source."
echo ""
echo "     - Un mécanisme d'authentification automatique par clé publique/clé privée"
echo "       va être mis en place pour que les scripts sur ce serveur puissent accéder"
echo "       en SSH à la machine distante sans saisie de mot de passe."
echo "       La sécurité de la machine distante dépend donc de la machine locale."
echo "       Si un intrus obtient l'accès root sur la machine locale, il aura"
echo "       automatiquement l'accès root sur la machine distante."
echo "       Songez-y si la machine distante à d'autres fonctions que la fonction"
echo "       de sauvegarde."

#NOTE: On pourrait n'utiliser qu'un seul disque dur sur la machine distante.
#      Il faudrait alors booter sur une distribution live disposant d'un serveur SSH
#      sur la machine distante.
#      L'inconvénient, c'est qu'en cas de reboot sur la machine distante (coupure de courant, maintenance,...),
#      il faut refaire la mise en place de l'authentification automatique.

DDLOCAL=""
while [ "$DDLOCAL" != "1" -a "$DDLOCAL" != "2" ]
do
	echo -e "$COLTXT"
	echo "Souhaitez-vous effectuer un miroir:"
	echo -e "     (${COLCHOIX}1${COLTXT}) local,"
	echo -e "     (${COLCHOIX}2${COLTXT}) distant."
	echo -e "Réponse: [${COLDEFAUT}1${COLTXT}] ${COLSAISIE}\c"
	read DDLOCAL

	if [ -z "$DDLOCAL" ]; then
		DDLOCAL="1"
	fi
done


#===================================================================
echo -e "$COLCMD"
echo '#!/bin/bash' > $tmp/traitement_disque_destination.sh
#Faut-il effectuer des initialisations de variables?
echo "DDLOCAL=$DDLOCAL" >> $tmp/traitement_disque_destination.sh
echo "DISK1=$DISK1" >> $tmp/traitement_disque_destination.sh
echo "PARTSWAP=$PARTSWAP" >> $tmp/traitement_disque_destination.sh
echo "PARTROOT=$PARTROOT" >> $tmp/traitement_disque_destination.sh
echo "PARTHOME=$PARTHOME" >> $tmp/traitement_disque_destination.sh
echo "PARTVARSE3=$PARTVARSE3" >> $tmp/traitement_disque_destination.sh
echo "PARTVAR=$PARTVAR" >> $tmp/traitement_disque_destination.sh

#cat $chemin/traitement_disque_destination.sh | sed -e 's!#!/bin/bash!!' >> $tmp/traitement_disque_destination.sh

echo '#Couleurs
COLTITRE="\033[1;35m"	# Rose
COLPARTIE="\033[1;34m"	# Bleu

COLTXT="\033[0;37m"	# Gris
COLCHOIX="\033[1;33m"	# Jaune
COLDEFAUT="\033[0;33m"	# Brun-jaune
COLSAISIE="\033[1;32m"	# Vert

COLCMD="\033[1;37m"	# Blanc

COLERREUR="\033[1;31m"	# Rouge
COLINFO="\033[0;36m"	# Cyan

#Dossier temporaire:
ladate=$(date +"%Y.%m.%d-%H.%M.%S");
tmp="/tmp/tmp.${ladate}"
mkdir -p "$tmp"

ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$1"
	echo -e "$COLTXT"
	read PAUSE
	if [ ! -z "$email" ]; then
		echo "ERREUR" > "$tmp/erreur.txt"
		echo -e "$1" >> "$tmp/erreur.txt"
		mail $email -s"[Mise en place Mirroring SSH] ERREUR" < "$tmp/erreur.txt"
		rm -f "$tmp/erreur.txt"
	fi
	#A décommenter après la phase de debug:
	#rm -fr $tmp
	echo -e "$COLTXT"
	exit 0
}

POURSUIVRE()
{
	if [ -z "$1" ]; then
		QUESTION="Peut-on poursuivre"
	else
		QUESTION="$1"
	fi

	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "$QUESTION? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
	done

	if [ "$REPONSE" != "o" ]; then
		ERREUR "Abandon!"
	fi
}

AFFICHHD()
{
	echo -e "$COLTXT"
	echo "Voici la liste des disques trouvés sur votre machine:"
	echo -e "$COLCMD"
	TEMOIN=""
	if /bin/dmesg | grep hd | grep drive | grep -v driver | grep -v ROM; then
		TEMOIN="OK"
	fi

	#/bin/dmesg | grep sd | grep drive | grep -v driver | grep -v ROM
	if /bin/dmesg | grep sd | grep SCSI | grep -v ROM; then
		TEMOIN="OK"
	fi

	if [ "$TEMOIN" != "OK" ]; then
		echo -e "${COLINFO}Les méthodes précédentes de détection n ont pas fonctionné."
		echo "Deux autres méthodes vont être tentées."
		echo "Si elles échouent, il vous faudra connaitre"
		echo -e "l identifiant (hda, hdb,...) du disque pour poursuivre.${COLCMD}"
		#Sur les IBM Thinkpad, les commandes précédentes ne donnent rien alors que /dev/hda est bien présent.
		#/bin/dmesg | grep dev | grep host | grep bus | grep target | grep lun | cut -d ":" -f 1 | sed -e "s/ //g" | sed -e "s!ide/host0/bus0/target0/lun0!hda!g" | sed -e "s!ide/host0/bus0/target1/lun0!hdb!g" | sed -e "s!ide/host0/bus1/target0/lun0!hdc!g" | sed -e "s!ide/host0/bus1/target1/lun0!hdd!g"
		#if /bin/dmesg | grep dev | grep host | grep bus | grep target | grep lun | cut -d ":" -f 1 | sed -e "s/ //g" | sed -e "s!ide/host0/bus0/target0/lun0!hda!g" | sed -e "s!ide/host0/bus0/target1/lun0!hdb!g" | sed -e "s!ide/host0/bus1/target0/lun0!hdc!g" | sed -e "s!ide/host0/bus1/target1/lun0!hdd!g"; then
		if /bin/dmesg | grep dev | grep host | grep bus | grep target | grep lun > /dev/null; then
			/bin/dmesg | grep dev | grep host | grep bus | grep target | grep lun | cut -d ":" -f 1 | sed -e "s/ //g" | sed -e "s!ide/host0/bus0/target0/lun0!hda!g" | sed -e "s!ide/host0/bus0/target1/lun0!hdb!g" | sed -e "s!ide/host0/bus1/target0/lun0!hdc!g" | sed -e "s!ide/host0/bus1/target1/lun0!hdd!g"
			TEMOIN="OK"
		fi
		#Une alternative sera: ls /dev/hd*
	fi

	if [ "$TEMOIN" != "OK" ]; then
		echo ""
		ls /dev/ | egrep "(hd|sd)" | grep -v "[0-9]" 2>/dev/null |while read A
		do
			if /sbin/fdisk -l /dev/$A | grep Blocks > /dev/null; then
				echo $A
				echo "OK" > /tmp/TEMOIN
			fi
		done
		if [ -e "/tmp/TEMOIN" ]; then
			TEMOIN=$(cat /tmp/TEMOIN)
			rm -f /tmp/TEMOIN
			echo -e "$COLINFO"
			echo "Un message éventuel indiquant:"
			echo -e "${COLERREUR}Disk /dev/XdY doesn t contain a valid partition table"
			echo -e "${COLINFO}signifie seulement que le périphérique /dev/XdY ne doit pas être un disque dur."
		fi
	fi

	if [ "$TEMOIN" != "OK" ]; then
		echo -e "$COLCMD"
		if ls /dev/hd* | grep "[0-9]" > /dev/null; then
			ls /dev/hd* | grep "[0-9]" | sed -e "s!/dev/!!g" | sed -e "s/[0-9]*//g"
		else
			echo -e "${COLINFO}Le(s) disque(s) dur(s) n a/ont pas été identifié(s) par mon script.\nCela ne vous empêche pas de poursuivre,\nmais il faut alors connaitre le périphérique...${COLTXT}"
		fi
	fi
}

echo -e "$COLPARTIE"
echo "********************************************"
echo "* Opérations sur le disque dur destination *"
echo "********************************************"

if [ "$DDLOCAL" = "1" ]; then
	DOSSMIRROR="/mirror"
else
	DOSSMIRROR="/tmp/mirror"
fi

AFFICHHD

DISK2OK="n"
while [ "$DISK2OK" != "o" ]
do
	echo -e "$COLTXT"
	echo -e "Quel est votre deuxième disque? [${COLDEFAUT}hdb${COLTXT}] ${COLSAISIE}\c"
	read  DISK2
	if [ -z "$DISK2" ]; then
		DISK2="hdb"
	fi

	DISK2OK=""
        while [ "$DISK2OK" != "o" -a "$DISK2OK" != "n" ]
        do
		echo -e "$COLTXT"
		echo -e "Votre deuxième disque est${COLINFO} $DISK2 ${COLTXT}"
		echo -e "Est-ce correct? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
		read DISK2OK
		if [ -z "$DISK2OK" ]; then
			DISK2OK="o"
		fi
        done
done

echo -e "$COLTXT"
echo "Test de la validité de votre choix."
if [ "$DDLOCAL" = "1" ]; then
	if [ "$DISK2" = "$DISK1"  ]; then
		echo -e "${COLERREUR}Erreur! Vous avez saisi la même valeur pour le 1er et le 2ème disque."
		echo -e "${COLERREUR}Le script ne peut mettre en place un mirroring sur le meme disque."
		echo -e "$COLTXT"
		exit 1
	fi
else
	#Arranger un test pour ne pas écraser le disque dur courant...
	if mount | grep "/dev/$DISK2 " > /dev/null; then
		echo -e "${COLERREUR}Erreur! Vous avez choisi un disque  actuellement monté."
		echo -e "${COLERREUR}Le disque dur /dev/$DISK2 sera intégralement repartitionné et reformaté."
		echo -e "${COLERREUR}Le script ne peut mettre en place un mirroring sur le disque de l OS distant."
		echo -e "$COLTXT"
		exit 1
	fi
fi

#NOTE: A revoir: On pourrait envisager un mirroring intégral
#      juste à des fins de sauvegarde sur le disque dur de l OS distant...
#      Il ne serait alors pas possible/commode de remplacer le disque source en cas de pépin,
#      mais la sauvegarde serait tout de même assurée.
#      REVOIR les tests effectués pour permettre une telle sauvegarde.

echo -e "$COLCMD"
#DISK2PARTS=`sfdisk -l /dev/$DISK2 2>/dev/null`
DISK2PARTS=`/sbin/sfdisk -l /dev/$DISK2 2>/dev/null`

if [ -z "$DISK2PARTS" ]; then
#if ! sfdisk -l /dev/$DISK2 2>/dev/null > /dev/null; then
        echo -e "${COLERREUR}Erreur! Aucun disque $DISK2 détecté."
        echo -e "${COLERREUR}Vous avez saisi une valeur erronée pour le 2ème disque."
        exit 1
fi

#recuperation des noms de partitions du disque 2
PARTSWAP_CIBLE=`echo $PARTSWAP | sed -e "s/$DISK1/$DISK2/"`
PARTROOT_CIBLE=`echo $PARTROOT | sed -e "s/$DISK1/$DISK2/"`
PARTHOME_CIBLE=`echo $PARTHOME | sed -e "s/$DISK1/$DISK2/"`
PARTVARSE3_CIBLE=`echo $PARTVARSE3 | sed -e "s/$DISK1/$DISK2/"`

echo -e "$COLTXT"
echo -e "Voici la liste des (futures) partitions de${COLINFO} $DISK2 ${COLTXT}"
echo -e "Partition SWAP :\t${COLINFO} $PARTSWAP_CIBLE ${COLTXT}"
echo -e "Partition Racine :\t${COLINFO} $PARTROOT_CIBLE ${COLTXT}"
if [ "$PARTVAR" != "aucune" ]; then
        PARTVAR_CIBLE=`echo $PARTVAR | sed -e "s/$DISK1/$DISK2/"`
        echo -e "Partition /VAR :\t${COLINFO} $PARTVAR_CIBLE ${COLTXT}"
fi
echo -e "Partition /HOME :\t${COLINFO} $PARTHOME_CIBLE ${COLTXT}"
echo -e "Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3_CIBLE ${COLTXT}"

REPONSE=""
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
	echo -e "$COLTXT"
	echo -e "Voulez-vous poursuivre l installation ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
	read REPONSE
	if [ -z "$REPONSE" ]; then
		REPONSE="o"
	fi
done
if [ "$REPONSE" = "n" ]; then
	echo -e "${COLERREUR}Action abandonnée, rien n a été modifié.${COLTXT}"
	exit 0
fi




echo -e "$COLPARTIE"
echo "**********************************"
echo "* Création des points de montage *"
echo "*  pour les partitions miroirs   *"
echo "**********************************"

echo -e "$COLTXT"
#Création des repertoires de travail si besoin
if  [ -e $DOSSMIRROR ]; then
	echo -e "Le répertoire $DOSSMIRROR existe déjà...."
else
	mkdir -p $DOSSMIRROR
fi

if  [ -e $DOSSMIRROR/part_root ]; then
	echo -e "Le répertoire $DOSSMIRROR/part_root existe déjà...."
else
	mkdir $DOSSMIRROR/part_root
fi

if [ "$PARTVAR" != "aucune" ]; then
        if  [ -e $DOSSMIRROR/part_var ]; then
        	echo -e "Le répertoire $DOSSMIRROR/part_var existe déjà...."
        else
        	mkdir $DOSSMIRROR/part_var
        fi
fi

if  [ -e $DOSSMIRROR/part_home ]; then
	echo -e "Le répertoire $DOSSMIRROR/part_home existe déjà...."
else
	mkdir $DOSSMIRROR/part_home
fi

if  [ -e $DOSSMIRROR/part_varse3 ]; then
	echo -e "Le répertoire $DOSSMIRROR/part_varse3 existe déjà...."
else
	mkdir $DOSSMIRROR/part_varse3
fi

echo "Terminé."





echo -e "$COLPARTIE"
echo "***************************"
echo "* Création des partitions *"
echo "***************************"

# Création des partitions du 2eme disque
REPONSE=""
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
        echo -e "$COLTXT"
        echo -e "Voulez-vous créer les partitions et formater le disque ${COLINFO}$DISK2 ${COLTXT}? (${COLCHOIX}o/n$COLTXT)"
        echo -e "${COLERREUR}Attention le contenu de $DISK2 sera effacé.${COLTXT}"
	echo -e "Réponse: ${COLSAISIE}\c"
        read REPONSE
done

echo ""
if [ "$REPONSE" = "o" ]; then
        echo -e "$COLTXT"
        echo "Création des partitions et des systèmes de fichiers:"
        echo -e "$COLCMD"
        #sfdisk /dev/$DISK2 < /tmp/part
        /sbin/sfdisk /dev/$DISK2 < /tmp/part
        if [ $? != 0 ]; then
		echo ""
                echo -e "${COLERREUR}Erreur lors de la création des partitions de $DISK2 "
                echo -e "Le script ne peut se poursuivre normalement."
		echo ""
                echo -e "${COLINFO}Vos disques ne sont peut-être pas strictement identiques."
                echo -e "Vous pouvez exécuter cfdisk et partitionner manuellement de la même façon que le 1er disque."

		echo -e "$COLTXT"
                echo -e "Pour rappel, voici l ordre dans lequel elles devront apparaître:"
                echo -e "Partition SWAP :\t${COLINFO} $PARTSWAP_CIBLE ${COLTXT}"
                echo -e "Partition Racine :\t${COLINFO} $PARTROOT_CIBLE ${COLTXT}"
                if [ "$PARTVAR" != "aucune" ]; then
	                echo -e "Partition /VAR :\t${COLINFO} $PARTVAR_CIBLE ${COLTXT}"
                fi
                echo -e "Partition /HOME :\t${COLINFO} $PARTHOME_CIBLE ${COLTXT}"
                echo -e "Partition /VAR/SE3 :\t${COLINFO} $PARTVARSE3_CIBLE ${COLTXT}"
                REPONSE=""
                while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
                do
                        echo -e "$COLTXT"
                        echo -e "Voulez-vous créer les partitions à la main maintenant? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
                        read REPONSE
                done
                if [ "$REPONSE" = "n" ]; then
                        echo -e "${COLERREUR}Opération annulée, rien n a été effectué !!! ${COLTXT}"
                        exit 1
                fi
                while [ "$REPONSE" != "1" -a "$REPONSE" != "2" ]
                do
                        echo -e "$COLTXT"
                        echo -e "Voulez-vous lancer:"
			echo -e "   (${COLCHOIX}1${COLTXT}) cfdisk"
			echo -e "       (j ai eu des problèmes avec le lancement de cfdisk en SSH)"
			echo -e "   (${COLCHOIX}2${COLTXT}) fdisk"
			echo -e "Choix: $COLSAISIE\c"
                        read REPONSE
                done
		echo -e "$COLCMD"
		if [ "$REPONSE" = "1" ]; then
	                /sbin/cfdisk /dev/$DISK2
		else
	                /sbin/fdisk /dev/$DISK2
		fi
        fi

	echo -e "$COLINFO"
	echo "Ne pas s affoler pour un message indiquant:"
	echo -e "${COLERREUR}sfdisk: ERROR: sector 0 does not have an msdos signature${COLINFO}"
	echo "lors du partitionnement du disque distant."
	echo "Cela survient notamment lorsque le disque dur n était pas encore partitionné."
	echo "C est sans conséquence pour la suite."

	POURSUIVRE

	echo -e "$COLTXT"
	echo "Création des systèmes de fichiers sur les partitions créées:"
	sleep 1
	if [ "$PARTVAR" != "aucune" ]; then
		#echo -e "$COLTXT"
		#echo -e "Partition /VAR :\t${COLINFO} $PARTVAR ${BLANC}"
		echo -e "$COLCMD"
		/sbin/mkswap /dev/$PARTSWAP_CIBLE  && /sbin/mke2fs -j /dev/$PARTROOT_CIBLE && /sbin/mke2fs -j /dev/$PARTVAR_CIBLE && /sbin/mkfs.xfs -f /dev/$PARTHOME_CIBLE && /sbin/mkfs.xfs -f /dev/$PARTVARSE3_CIBLE
	else
		echo -e "$COLCMD"
		/sbin/mkswap /dev/$PARTSWAP_CIBLE  && /sbin/mke2fs -j /dev/$PARTROOT_CIBLE && /sbin/mkfs.xfs -f /dev/$PARTHOME_CIBLE && /sbin/mkfs.xfs -f /dev/$PARTVARSE3_CIBLE
	fi


        if [ $? != 0 ]; then
                echo -e "${COLERREUR}Erreur lors du formatage des partitions de $DISK2 "
                echo -e "Le script ne peut se poursuivre${BLANC}"
                exit 1
        fi

	#Renseignement d un fichier pour permettre la récupération des infos par le script mirroring_ssh.sh
	echo "DISK2=$DISK2
PARTSWAP_CIBLE=$PARTSWAP_CIBLE
PARTROOT_CIBLE=$PARTROOT_CIBLE
PARTHOME_CIBLE=$PARTHOME_CIBLE
PARTVARSE3_CIBLE=$PARTVARSE3_CIBLE
PARTVAR_CIBLE=$PARTVAR_CIBLE" > $DOSSMIRROR/liste_infos_disque2.txt

	echo -e "$COLTITRE"
	echo "Partitionnement et création des systèmes de fichiers terminés."

	#sleep 1

	POURSUIVRE

	clear

fi

exit 0
' >> $tmp/traitement_disque_destination.sh

chmod +x $tmp/traitement_disque_destination.sh
#===================================================================



if [ "$DDLOCAL" = "1" ]; then
	echo -e "$COLCMD"
	#****************************************************************
	#****************************************************************
	#A REVOIR
	#Il va falloir regénérer un $tmp/traitement_disque_destination.sh
	#avec les variables $DISK1, $PARTSWAP,...
	#
	#A TESTER:
	#Peut-être que le problème ne se pose que lors d'une copie/exécution à travers SSH...?
	#****************************************************************
	#****************************************************************


	#$chemin/traitement_disque_destination.sh

	$tmp/traitement_disque_destination.sh
else
	PARAMDISTOK=""
	while [ "$PARAMDISTOK" != "o" ]
	do
		echo -e "$COLTXT"
		echo "Vous allez devoir fournir quelques renseignements concernant"
		echo "la machine distante."

		REPIPDISTOK=""
		while [ "$REPIPDISTOK" != "o" ]
		do
			echo -e "$COLTXT"
			echo -e "IP de la machine distante: ${COLSAISIE}\c"
			read IPDISTANT
			#On peut aussi mettre un nom DNS.

			echo -e "$COLINFO"
			echo "Test..."
			echo -e "$COLCMD\c"
			if ping -c1 $IPDISTANT | grep "1 packets received" > /dev/null; then
				echo -e "${COLINFO}La machine $IPDISTANT a répondu au ping.${COLTXT}"
				REPIPDISTOK="o"
			else
				echo -e "${COLERREUR}La machine $IPDISTANT n'a pas répondu au ping.${COLTXT}"

				REPPOURSUIVRE=""
				while [ "$REPPOURSUIVRE" != "1" -a "$REPPOURSUIVRE" != "2" -a "$REPPOURSUIVRE" != "3" ]
				do
					echo -e "$COLTXT"
					echo "Souhaitez-vous:"
					echo -e "     (${COLCHOIX}1${COLTXT}) poursuivre néanmoins"
					echo "         (s'il est normal que la machine ne réponde pas au ping),"
					echo -e "     (${COLCHOIX}2${COLTXT}) corriger,"
					echo -e "     (${COLCHOIX}3${COLTXT}) ou abandonner?"
					echo -e "Réponse: ${COLSAISIE}\c"
					read REPPOURSUIVRE
				done

				if [ "$REPPOURSUIVRE" = "1" ]; then
					REPIPDISTOK="o"
				fi

				if [ "$REPPOURSUIVRE" = "3" ]; then
					ERREUR 'Abandon!'
				fi
			fi
		done

		#AI-JE VRAIMENT BESOIN DE CE MOT DE PASSE PAR LA SUITE???
		#echo -e "$COLINFO"
		#echo "Pour la saisie du mot de passe, il ne va rien s'afficher (sécurité),"
		#echo "mais ne vous inquiétez pas pour autant pour le fonctionnement de"
		#echo "votre clavier et de vos doigts carrés;o)."
		#echo -e "$COLTXT"
		#echo -e "Mot de passe root distant: ${COLMDP}\c"
		#stty -echo
		#read MDPDISTANT
		#stty echo
		echo ""

		REPDISTOK=""
		while [ "$REPDISTOK" != "o" -a "$REPDISTOK" != "n" ]
		do
			echo -e "$COLINFO"
			echo "Vous avez choisi pour le mirroring distant les paramètres suivants:"
			echo -e "     - IP:$COLINFO $IPDISTANT $COLTXT"
			echo -e "$COLTXT"
			echo -e "Est-ce correct? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
			read REPDISTOK

			if [ -z "$REPDISTOK" ]; then
				REPDISTOK="o"
			fi
		done

		if [ "$REPDISTOK" = "o" ]; then
			PARAMDISTOK="o"
		else
			REPDISTOK=""
			while [ "$REPDISTOK" != "o" -a "$REPDISTOK" != "n" ]
			do
				echo -e "$COLTXT"
				echo -e "Souhaitez-vous corriger (${COLCHOIX}1${COLTXT}) ou abandonner (${COLCHOIX}2${COLTXT})? ${COLSAISIE}\c"
				read REPDISTOK
			done

			if [ "$REPDISTOK" = "2" ]; then
				ERREUR "Abandon"
			fi
		fi
	done

	echo -e "$COLINFO"
	echo "NOTE: Pour effectuer le mirroring vers une distribution Live Digloo,"
	echo "      il est nécessaire de disposer d'une version avec rsync présent,"
	echo "      pas seulement dans l'image installée, mais aussi dans la distribution"
	echo "      Digloo Live elle-même."
	echo "      Il faut de plus modifier un paramètre du fichier /etc/ssh/sshd_config"
	echo "      En effet, il n'est pas possible, par défaut, de se connecter en root"
	echo "      à une Digloo Live."
	echo "      Il faut modifier la ligne 'PermitRootLogin no' en 'PermitRootLogin yes'"
	echo "      Il faut ensuite arrêter et redémarrer ssh:"
	echo "         /etc/init.d/ssh stop"
	echo "         /etc/init.d/ssh start"


	echo -e "$COLTXT"
	#echo "Repère pour la colonne=======================================================80"
	echo "Mise en place de la clé publique locale"
	echo "dans le fichier authorized_keys distant."
	echo "Il va vous être demandé de saisir une PASSPHRASE lors de la génération des"
	echo "clés publique/privée."
	echo -e "Il est indispensable de laisser ${COLERREUR}vide${COLTXT} cette PASSPHRASE."
	echo "Et vous allez ensuite devoir saisir le mot de passe root distant:"
	echo -e "$COLCMD"
	#cat $clepublique | ssh root@$IPDISTANT 'sh -c "mkdir -p /root/.ssh && cat - >>/root/.ssh/authorized_keys && chmod 600 /root/.ssh/authorized_keys"'

	#if [ ! -e "/root/.ssh/id_dsa" -o ! -e "/root/.ssh/id_dsa.pub" ]; then mkdir -p /root/.ssh && ssh-keygen -t dsa -f ~/.ssh/id_dsa; else echo -e "${COLINFO}Les clés publique/privée existent déjà.${$COLCMD}";fi && cat ~/.ssh/id_dsa.pub | ssh root@$IPDISTANT 'sh -c "mkdir -p /root/.ssh && cat - >>/root/.ssh/authorized_keys && chmod 600 /root/.ssh/authorized_keys"'
	if [ ! -e "/root/.ssh/id_dsa" -o ! -e "/root/.ssh/id_dsa.pub" ]; then
		mkdir -p /root/.ssh && ssh-keygen -t dsa -f ~/.ssh/id_dsa
	else
		echo -e "${COLINFO}Les clés publique/privée existent déjà.${COLCMD}"
	fi
	cat ~/.ssh/id_dsa.pub | ssh root@$IPDISTANT 'sh -c "mkdir -p /root/.ssh && cat - >>/root/.ssh/authorized_keys && chmod 600 /root/.ssh/authorized_keys"'

	if [ "$?" != "0" ]; then
		ERREUR "La mise en place de la clé publique locale\ndans le fichier authorized_keys distant a échoué."
	fi


	#====================================================

	#echo '' > $tmp/passwdgroupmin.sh

	echo -e "$COLINFO"
	echo "Si la distribution distante est une distribution SE3 installée sur disque dur,"
	echo "vous pouvez répondre 'n' à la question suivante."
	echo "En revanche, si le système distant est une distribution live (SysRescCD,"
	echo "Digloo (live)), il convient, pour éviter des problèmes d'uid/uidNumber"
	echo "et gid/gidNumber, de remanier (en RAM) les fichiers suivants:"
	echo "   - /etc/passwd"
	echo "   - /etc/group"

	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "La distribution distante est-elle une distribution live? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}n${COLTXT}] $COLSAISIE\c"
		read REPONSE

		if [ "$REPONSE" = "" ]; then
			REPONSE="n"
		fi
	done

	if [ "$REPONSE" = "o" ]; then

		echo '#!/bin/bash

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

#Dossier temporaire:
ladate=$(date +"%Y.%m.%d-%H.%M.%S");

ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$1"
	echo -e "$COLTXT"
	read PAUSE
	exit 0
}


echo -e "$COLTITRE"
echo "*************************************************"
echo "* Script de vidage de /etc/passwd et /etc/group *"
echo "*************************************************"

echo -e "$COLINFO"
echo "Pour des besoins de synchronisation RSYNC+SSH entre SE3 SysRescCD live,"
echo "j ai bricolé ce script réduisant au minimum les fichiers /etc/passwd"
echo "et /etc/group"
echo "Cela permet de ne pas avoir des correspondances uid/uidNumber et gid/gidNumber"
echo "différentes entre les deux OS."

REPONSE=""
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
	echo -e "$COLTXT"
	echo -e "Souhaitez-vous mettre en place ces fichiers réduits? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}n${COLTXT}] $COLSAISIE\c"
	read REPONSE

	if [ "$REPONSE" = "" ]; then
		REPONSE="n"
		ERREUR "Vous avez souhaité abandonner."
	fi
done

if [ "$REPONSE" = "o" ]; then
	echo -e "$COLCMD"
	cp /etc/passwd /tmp/passwd.${ladate}
	cp /etc/group /tmp/group.${ladate}

	REPONSE=""
	while [ "$REPONSE" != "1" -a "$REPONSE" != "2" ]
	do
		echo -e "$COLTXT"
		echo "La distribution est-elle:"
		echo -e "   (${COLCHOIX}1${COLTXT}) SysRescCD"
		echo -e "   (${COLCHOIX}2${COLTXT}) Digloo (live)"
		echo -e "Réponse: $COLSAISIE\c"
		read REPONSE
	done

	case $REPONSE in
		1)
			echo "root:x:0:0:root:/root:/bin/zsh" > /etc/passwd
			echo "sshd:x:22:22:sshd:/var/empty:/dev/null" >> /etc/passwd
			echo "root::0:root" > /etc/group
			echo "sshd::22:" >> /etc/group
		;;
		2)
			echo "root:x:0:0:root:/root:/bin/bash" > /etc/passwd
			echo "sshd:x:100:65534::/var/run/sshd:/bin/false" >> /etc/passwd
			echo "root:x:0:" > /etc/group
			#echo "nogroup:x:65534:" >> /etc/group

			#cat /etc/passwd | egrep "(root|sshd)" > /etc/passwd
			#cat /etc/group | grep root > /etc/group

			#NOTE: Sur Digloo, il faut effectuer:
			# passwd
			# ifconfig eth0 192.168.52.8
			#Ne pas s inquiéter pour un message: "eth0: duplicate address detected"
			# sed -e "s/PermitRootLogin no/PermitRootLogin yes/" /etc/ssh/sshd_config > /tmp/sshd_config.tmp
			# cp /tmp/sshd_config.tmp /etc/ssh/sshd_config
			# /etc/init.d/ssh stop
			# /etc/init.d/ssh start
			#Si vous en êtes ici, vous avez déjà dû le faire;o).
		;;
	esac

	echo -e "$COLINFO"
	echo "Voilà les fichiers mis en place:"
	echo -e "$COLTXT"
	echo "/etc/passwd:"
	echo -e "$COLCMD\c"
	cat /etc/passwd

	echo -e "$COLTXT"
	echo "/etc/group:"
	echo -e "$COLCMD\c"
	cat /etc/group

	#echo -e "$COLINFO"
	#echo "Une sauvegarde des fichiers initiaux a été effectuée dans /tmp"
	#echo ""
	#echo "Pour mes bricolages de mirroring, il reste à:"
	#echo "   - configurer le réseau: net-setup eth0"
	#echo "   - mettre un mot de passe à root (passwd)"
	#echo "   - démarrer le serveur SSH: /etc/init.d/sshd start"
fi


echo -e "${COLTITRE}"
echo "***********"
echo "* Terminé *"
echo "***********"
echo -e "${COLTXT}"
echo "Appuyez sur ENTREE pour poursuivre."
read PAUSE
' > $tmp/passwdgroupmin.sh

		echo -e "$COLTXT"
		echo "Copie du script passwdgroupmin.sh vers la machine distante."
		echo -e "$COLCMD"
		ssh root@$IPDISTANT "mkdir -p /root/tmp"
		scp $tmp/passwdgroupmin.sh root@$IPDISTANT:/root/tmp/
		#scp passwdgroupmin.sh root@$IPDISTANT:/root/tmp/

		echo -e "$COLTXT"
		echo "Exécution du script distant passwdgroupmin.sh "
		echo -e "$COLCMD"
		ssh root@$IPDISTANT "sh /root/tmp/passwdgroupmin.sh "
	fi
	#====================================================


	echo -e "$COLTXT"
	echo "Copie du script traitement_disque_destination.sh vers la machine distante."
	echo -e "$COLCMD"
	ssh root@$IPDISTANT "mkdir -p /root/tmp"
	scp $tmp/traitement_disque_destination.sh root@$IPDISTANT:/root/tmp/
	scp /tmp/part root@$IPDISTANT:/tmp/

	echo -e "$COLTXT"
	echo "Exécution du script distant traitement_disque_destination.sh"
	echo -e "$COLCMD"
	ssh root@$IPDISTANT "sh /root/tmp/traitement_disque_destination.sh"
fi



# Test du succès du script traitement_disque_destination.sh
# Ou plutôt de l'absence de renvoi d'un code d'erreur.
#if [ "$?" != "0" ]; then
#	ERREUR "Le script traitement_disque_destination.sh a renvoyé un code d'erreur."
#fi

if [ "$?" != "0" ]; then
	echo -e "$COLERREUR"
	echo "Il semble que le script traitement_disque_destination.sh ait renvoyé"
	echo "un code d'erreur."

	echo -e "$COLINFO"
	echo "Cela m'est arrivé avec un message"
	echo -e "    ${COLERREUR}TERM environment variable not set.${COLINFO}"
	echo "dont je n'ai pas réussi à identifier la source."

	POURSUIVRE "Souhaitez-vous poursuivre"
fi



#Il faut récupérer les infos suivantes dans le shell courant qui est parent de celui de traitement_disque_destination.sh:
#	DISK2
#	PARTSWAP_CIBLE
#	PARTROOT_CIBLE
#	PARTHOME_CIBLE
#	PARTVARSE3_CIBLE
#	PARTVAR_CIBLE


echo -e "$COLTXT"
echo "Récupération des infos sur les partitions du disque destination."
echo -e "$COLCMD"
if [ "$DDLOCAL" = "2" ]; then
	mkdir -p /mirror
	scp root@$IPDISTANT:/tmp/mirror/liste_infos_disque2.txt /mirror/
fi
if [ ! -e "/mirror/liste_infos_disque2.txt" ]; then
	ERREUR "Les infos concernant le disque destination n'ont pas pu être récupérées."
fi

DISK2=$(cat /mirror/liste_infos_disque2.txt | grep "^DISK2=" | sed -e "s!^DISK2=!!")
PARTSWAP_CIBLE=$(cat /mirror/liste_infos_disque2.txt | grep "^PARTSWAP_CIBLE=" | sed -e "s!^PARTSWAP_CIBLE=!!")
PARTROOT_CIBLE=$(cat /mirror/liste_infos_disque2.txt | grep "^PARTROOT_CIBLE=" | sed -e "s!^PARTROOT_CIBLE=!!")
PARTHOME_CIBLE=$(cat /mirror/liste_infos_disque2.txt | grep "^PARTHOME_CIBLE=" | sed -e "s!^PARTHOME_CIBLE=!!")
PARTVARSE3_CIBLE=$(cat /mirror/liste_infos_disque2.txt | grep "^PARTVARSE3_CIBLE=" | sed -e "s!^PARTVARSE3_CIBLE=!!")
PARTVAR_CIBLE=$(cat /mirror/liste_infos_disque2.txt | grep "^PARTVAR_CIBLE=" | sed -e "s!^PARTVAR_CIBLE=!!")




#A ARRANGER: Utiliser une variable $scriptmirror
#            pour permettre à la fois un mirroring local
#            et un mirroring distant.
if [ "$DDLOCAL" = "1" ]; then
	scriptmirror="mirror_rsync.sh"
else
	scriptmirror="mirror_rsync_distant.sh"
fi
#ATTENTION: La variable $scriptmirror doit être définie avant les ajouts en crontab.






echo -e "$COLPARTIE"
echo "*****************************************"
echo "* Planification des tâches de mirroring *"
echo "*****************************************"

######### traitement de la crontab #############
REPONSE=""
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
	echo -e "$COLTXT"
	echo -e "Voulez-vous mettre en place le script rsync en crontab ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}]"
	echo -e "Il se lancera tous les jours ouvrables à l'heure de votre choix."
	echo -e "Il vous sera aussi proposé de le lancer plusieurs fois par jour."
	echo -e "Réponse: ${COLSAISIE}\c"
	read REPONSE
	if [ -z "$REPONSE" ]; then
		REPONSE="o"
	fi
done

if [ "$REPONSE" = "o" ]; then
#####
	CRONAJOUT=""
        while [ "$CRONAJOUT" != "n" ]
        do
		echo -e "$COLTXT"
                echo -e "Vous allez devoir préciser à quel moment de la journée le script s'exécutera."
                echo
                echo -e "Veuillez indiquer les heures et les minutes sous la forme hh:mn [${COLDEFAUT}02:30${COLTXT}] $COLSAISIE\c"
                read HMCRON
                if [ -z "$HMCRON" ]; then
                        HMCRON="02:30"
                fi

                CRONOK=""
                while [ "$CRONOK" != "o" -a "$CRONOK" != "n" ]
                do
			echo -e "$COLTXT"
			echo -e "Vous voulez que le script se lance tous les jours à $HMCRON"
			echo -e "Est-ce correct? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
			read CRONOK
			if [ -z "$CRONOK" ]; then
				CRONOK="o"
			fi
                done
                        if [ "$CRONOK" = "o" ]; then
				echo -e "$COLCMD"
                                MCRON=`echo $HMCRON | cut -d: -f2`
                                HCRON=`echo $HMCRON | cut -d: -f1`
                                echo "$MCRON $HCRON * * * root /mirror/$scriptmirror" >> /etc/crontab
				echo -e "$COLTXT"
                                echo "Modification de la crontab effectuée."

				CRONAJOUT=""
                                while [ "$CRONAJOUT" != "o" -a "$CRONAJOUT" != "n" ]
                                do
					echo -e "$COLTXT"
					echo "Vous avez choisi que le script se lance tous les jours à $HMCRON"
					echo -e "Voulez-vous qu'il se lance également à un autre moment ? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
					read CRONAJOUT
                                done
                        fi
        done
fi


#Ne faut-il pas redémarrer cron?







echo -e "$COLPARTIE"
echo "**************************"
echo "* Configuration courrier *"
echo "**************************"

##########
# Configuration de l'envoi de mail ####
echo -e "$COLINFO"
echo -e "En cas de problème lors du mirroring des disques,"
echo -e "le script vous previendra par mail."
echo -e "Pour que cela soit possible, Exim doit être configuré."
echo -e "Il ne l'est pas par défaut lors d'une installation avec Digloo."
echo -e "Vous pourrez configurer Exim plus tard en lançant /usr/sbin/eximconfig"
REPONSE=""
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
	echo -e "$COLTXT"
	echo -e "Préférez-vous configurer Exim immédiatement ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}]"
	echo
	echo -e "Réponse: $COLSAISIE\c"
	read REPONSE
	if [ -z "$REPONSE" ]; then
		REPONSE="o"
	fi
done
if [ "$REPONSE" = "o" ]; then
	echo -e "$COLTXT"
	echo -e "Lancement de la configuration d'exim."
	echo -e "En général, la configuration à choisir est la N°3."
	# echo -e "Appuyez sur entree pour continuer"
	echo -e "$COLCMD"
	/usr/sbin/eximconfig
fi

clear
MAIL_ADMINOK="n"
while [ "$MAIL_ADMINOK" != "o" ]
do
	echo -e "$COLTXT"
        echo "Il vous faut choisir l'adresse mail qui recevra les rapports d'erreur."
	echo "Par exemple : admin@votre_domaine"
        echo -e "Quelle adresse mail voulez-vous utiliser? $COLSAISIE\c"
        read MAIL_ADMIN

	echo -e "$COLTXT"
	echo -e "Vous avez choisi ${COLINFO}${MAIL_ADMIN}${COLTXT} comme adresse mail."
        echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) [${COLDEFAUT}o${COLTXT}] ${COLSAISIE}\c"
        read MAIL_ADMINOK
        if [ -z "$MAIL_ADMINOK" ]; then
                MAIL_ADMINOK="o"
        fi
done









################# Création des scripts ########################

echo -e "$COLPARTIE"
echo "************************"
echo "* Création des scripts *"
echo "************************"

echo -e "$COLTXT"
echo "Création des scripts..."
if [ "$DDLOCAL" = "2" ]; then
	echo "Et copie des scripts vers la machine $IPDISTANT"
fi

echo -e "$COLCMD"
mkdir -p /mirror

# Création du script rsync
touch /mirror/$scriptmirror
chmod 700 /mirror/$scriptmirror

if [ "$DDLOCAL" = "1" ]; then
	LOCALISATION_DISQUE="local"
	DOSSMIRROR="/mirror"
else
	LOCALISATION_DISQUE="$IPDISTANT"
	DOSSMIRROR="/tmp/mirror"
fi




if [ "$DDLOCAL" = "1" ]; then
	scriptumountdisk2="umount_$DISK2.sh"
else
	scriptumountdisk2="umount_${IPDISTANT}_$DISK2.sh"
fi
#Cette variable doit être initialisée ici parce qu'elle est utilisée en début de script $scriptmirror.


echo "#!/bin/bash" > /mirror/$scriptmirror

if [ "$DDLOCAL" = "1" ]; then
	echo "/mirror/$scriptumountdisk2 2>/dev/null" >> /mirror/$scriptmirror
else
	echo "scp /mirror/$scriptumountdisk2 root@$IPDISTANT:$DOSSMIRROR/" >> /mirror/$scriptmirror
	echo "ssh root@$IPDISTANT \"$DOSSMIRROR/$scriptumountdisk2 2>/dev/null\"" >> /mirror/$scriptmirror
fi

echo 'FICHIERLOG="/mirror/log_rsync_'${LOCALISATION_DISQUE}'_`date +%a%Hh%M`"' >> /mirror/$scriptmirror
echo 'touch $FICHIERLOG' >> /mirror/$scriptmirror
echo 'echo "Fichier de log du" `date` > $FICHIERLOG '  >> /mirror/$scriptmirror




# partition /
echo 'echo "Montage de la partition Racine" '>> /mirror/$scriptmirror
if [ "$DDLOCAL" = "1" ]; then
	echo "mount -t ext3 /dev/$PARTROOT_CIBLE /mirror/part_root" >> /mirror/$scriptmirror
else
	echo "ssh root@$IPDISTANT \"mount -t ext3 /dev/$PARTROOT_CIBLE $DOSSMIRROR/part_root\"" >> /mirror/$scriptmirror
fi

echo -e 'if [ $? != 0 ]; then' >> /mirror/$scriptmirror
echo -e "echo \"** ERREUR ** lors du montage de la partition / de $DISK2\"  | tee -a \$FICHIERLOG " >> /mirror/$scriptmirror
echo -e 'echo "Le script ne peut se poursuivre." | tee -a $FICHIERLOG '>> /mirror/$scriptmirror
echo -e "echo \"VÉRIFIEZ LE BON FONCTIONNEMENT DU $DISK2\" | tee -a \$FICHIERLOG " >> /mirror/$scriptmirror
echo -e "touch /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"Rapport de fonctionnement du script de mirroring lors de son lancement.\" > /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"Voici les problèmes constatés:\" >> /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"\" >> /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e 'cat $FICHIERLOG >> /mirror/mail_alerte.txt' >> /mirror/$scriptmirror
echo -e "mail $MAIL_ADMIN -s \"[Alerte /mirror/$scriptmirror] Pb avec le script\" < /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "exit 1" >> /mirror/$scriptmirror
echo -e "fi" >> /mirror/$scriptmirror

echo 'echo  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
echo 'echo "rsync de la partition Racine" | tee $FICHIERLOG' >> /mirror/$scriptmirror
echo 'echo  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror

if [ "$DDLOCAL" = "1" ]; then
	if [ "$PARTVAR" != "aucune" ]; then
		#echo 'rsync -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/cdrom/* --exclude=/var/*  / /mirror/part_root | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		echo '/usr/bin/rsync -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/cdrom/* --exclude=/var/*  / /mirror/part_root | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	else
		#echo 'rsync -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/cdrom/* --exclude=/var/se3/*  / /mirror/part_root | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		echo '/usr/bin/rsync -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/cdrom/* --exclude=/var/se3/*  / /mirror/part_root | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	fi
else
	#rsync -az -e ssh /home/tests_rsync/rsync root@192.168.52.102:/home/tests_rs
	if [ "$PARTVAR" != "aucune" ]; then
		#echo 'rsync -e ssh -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/cdrom/* --exclude=/var/*  / root@'$IPDISTANT':'$DOSSMIRROR'/part_root | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		echo '/usr/bin/rsync -e ssh --rsync-path=/usr/bin/rsync -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/cdrom/* --exclude=/var/*  / root@'$IPDISTANT':'$DOSSMIRROR'/part_root | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	else
		#echo 'rsync -e ssh -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/cdrom/* --exclude=/var/se3/*  / root@'$IPDISTANT':'$DOSSMIRROR'/part_root | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		echo '/usr/bin/rsync -e ssh --rsync-path=/usr/bin/rsync -av --delete --exclude=/home/* --exclude=/mirror/ --exclude=/tmp/* --exclude=/var/lock/* --exclude=/proc/* --exclude=/cdrom/* --exclude=/var/se3/*  / root@'$IPDISTANT':'$DOSSMIRROR'/part_root | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	fi
fi





if [ "$DDLOCAL" = "1" ]; then
	scriptinstalllilodisk2="install_lilo_$DISK2.sh"
else
	scriptinstalllilodisk2="install_lilo_${IPDISTANT}_$DISK2.sh"
fi

#Mise en place de LILO
if [ "$DDLOCAL" = "1" ]; then
	echo "if [ -e /mirror/$scriptinstalllilodisk2 ]; then" >> /mirror/$scriptmirror
	echo "echo \"Installation de lilo sur $DISK2\"" >> /mirror/$scriptmirror
	echo "/mirror/$scriptinstalllilodisk2" >> /mirror/$scriptmirror
	echo "fi" >> /mirror/$scriptmirror
	echo "umount /dev/$PARTROOT_CIBLE" >> /mirror/$scriptmirror
	echo "" >> /mirror/$scriptmirror
else
	echo "if [ -e /mirror/$scriptinstalllilodisk2 ]; then" >> /mirror/$scriptmirror
	echo "echo \"Installation de lilo sur $DISK2\"" >> /mirror/$scriptmirror
	echo "scp /mirror/$scriptinstalllilodisk2 root@$IPDISTANT:$DOSSMIRROR/" >> /mirror/$scriptmirror
	echo "ssh root@$IPDISTANT \"$DOSSMIRROR/$scriptinstalllilodisk2\"" >> /mirror/$scriptmirror
	echo "fi" >> /mirror/$scriptmirror
	echo "ssh root@$IPDISTANT \"umount /dev/$PARTROOT_CIBLE\"" >> /mirror/$scriptmirror
	echo "" >> /mirror/$scriptmirror
fi







#partition /var si elle existe
if [ "$PARTVAR" != "aucune" ]; then

	echo 'echo "Montage de la partition /VAR" '>> /mirror/$scriptmirror
	if [ "$DDLOCAL" = "1" ]; then
		echo "mount -t ext3 /dev/$PARTVAR_CIBLE /mirror/part_var" >> /mirror/$scriptmirror
	else
		echo "ssh root@$IPDISTANT \"mount -t ext3 /dev/$PARTVAR_CIBLE $DOSSMIRROR/part_var\"" >> /mirror/$scriptmirror
	fi

	echo -e 'if [ $? != 0 ]; then' >> /mirror/$scriptmirror
	echo -e "echo \"** ERREUR ** lors du montage de la partition /var de $DISK2\"  | tee -a \$FICHIERLOG " >> /mirror/$scriptmirror
	echo -e 'echo "Le script ne peut se poursuivre." | tee -a $FICHIERLOG '>> /mirror/$scriptmirror
	echo -e "echo \"VÉRIFIEZ LE BON FONCTIONNEMENT DU $DISK2\" | tee -a \$FICHIERLOG " >> /mirror/$scriptmirror
	echo -e "touch /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
	echo -e "echo \"Rapport de fonctionnement du script de mirroring lors de son lancement.\" > /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
	echo -e "echo \"Voici les problèmes constatés:\" >> /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
	echo -e "echo \"\" >> /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
	echo -e 'cat $FICHIERLOG >> /mirror/mail_alerte.txt' >> /mirror/$scriptmirror
	echo -e "mail $MAIL_ADMIN -s \"[Alerte /mirror/$scriptmirror] Pb avec le script\" < /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
	echo -e "exit 1" >> /mirror/$scriptmirror
	echo -e "fi" >> /mirror/$scriptmirror

	echo 'echo  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	echo 'echo "rsync de la partition /var" | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	echo 'echo  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	if [ "$DDLOCAL" = "1" ]; then
		#echo 'rsync -av --delete --exclude=/se3/* /var/* /mirror/part_var | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		#echo 'rsync -av --delete --exclude=/se3/* --exclude=/var/lock/* /var/* /mirror/part_var | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		echo '/usr/bin/rsync -av --delete --exclude=/se3/* --exclude=/var/lock/* /var/* /mirror/part_var | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		echo "umount /dev/$PARTVAR_CIBLE " >> /mirror/$scriptmirror
	else
		#echo 'rsync -e ssh -av --delete --exclude=/se3/* /var/* root@'$IPDISTANT':/mirror/part_var | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		#echo 'rsync -e ssh -av --delete --exclude=/se3/* --exclude=/var/lock/* /var/* root@'$IPDISTANT':'$DOSSMIRROR'/part_var | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
		echo '/usr/bin/rsync -e ssh --rsync-path=/usr/bin/rsync -av --delete --exclude=/se3/* --exclude=/var/lock/* /var/* root@'$IPDISTANT':'$DOSSMIRROR'/part_var | tee -a $FICHIERLOG' >> /mirror/$scriptmirror



		#echo 'echo "*********"' >> /mirror/$scriptmirror
		#echo 'echo "* PAUSE *"' >> /mirror/$scriptmirror
		#echo 'echo "*********"' >> /mirror/$scriptmirror
		#echo 'read PAUSE' >> /mirror/$scriptmirror



		echo "ssh root@$IPDISTANT \"umount /dev/$PARTVAR_CIBLE\" " >> /mirror/$scriptmirror
	fi
fi



#partition home
echo 'echo "Montage de la partition HOME " '>> /mirror/$scriptmirror

if [ "$DDLOCAL" = "1" ]; then
	echo "mount -t xfs /dev/$PARTHOME_CIBLE /mirror/part_home" >> /mirror/$scriptmirror
else
	echo "ssh root@$IPDISTANT \"mount -t xfs /dev/$PARTHOME_CIBLE $DOSSMIRROR/part_home\"" >> /mirror/$scriptmirror
fi

echo -e 'if [ $? != 0 ]; then' >> /mirror/$scriptmirror
echo -e "echo \"** ERREUR ** lors du montage de la partition /home de $DISK2\"  | tee -a \$FICHIERLOG " >> /mirror/$scriptmirror
echo -e 'echo "Le script ne peut se poursuivre." | tee -a $FICHIERLOG '>> /mirror/$scriptmirror
echo -e "echo \"VÉRIFIEZ LE BON FONCTIONNEMENT DU $DISK2\" | tee -a \$FICHIERLOG " >> /mirror/$scriptmirror
echo -e "touch /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"Rapport de fonctionnement du script de mirroring lors de son lancement.\" > /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"Voici les problèmes constatés:\" >> /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"\" >> /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e 'cat $FICHIERLOG >> /mirror/mail_alerte.txt' >> /mirror/$scriptmirror
echo -e "mail $MAIL_ADMIN -s \"[Alerte /mirror/$scriptmirror] Pb avec le script\" < /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "exit 1" >> /mirror/$scriptmirror
echo -e "fi" >> /mirror/$scriptmirror

echo 'echo  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
echo 'echo "rsync de la partition /home" | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
echo 'echo  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
if [ "$DDLOCAL" = "1" ]; then
	#echo 'rsync -av --delete /home/*  /mirror/part_home | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	echo '/usr/bin/rsync -av --delete /home/*  /mirror/part_home | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	echo "cd /home" >> /mirror/$scriptmirror
	echo "echo \"Sauvegarde / Restauration des ACLS en cours pour /home ....\"" >> /mirror/$scriptmirror
	echo "getfacl -R . > /mirror/part_home/list_acls.txt" >> /mirror/$scriptmirror
	echo "cd /mirror/part_home/" >> /mirror/$scriptmirror
	echo "setfacl --restore=list_acls.txt"  >> /mirror/$scriptmirror
	echo "cd /" >> /mirror/$scriptmirror
	echo "umount /dev/$PARTHOME_CIBLE" >> /mirror/$scriptmirror
else
	#echo 'rsync -e ssh -av --delete /home/* root@'$IPDISTANT':'$DOSSMIRROR'/part_home | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	echo '/usr/bin/rsync -e ssh --rsync-path=/usr/bin/rsync -av --delete /home/* root@'$IPDISTANT':'$DOSSMIRROR'/part_home | tee -a $FICHIERLOG' >> /mirror/$scriptmirror

	echo "cd /home" >> /mirror/$scriptmirror
	echo "echo \"Sauvegarde / Restauration des ACLS en cours pour /home ....\"" >> /mirror/$scriptmirror
	echo "getfacl -R . > /mirror/part_home_list_acls.txt" >> /mirror/$scriptmirror

	echo "scp /mirror/part_home_list_acls.txt root@$IPDISTANT:$DOSSMIRROR/part_home/" >> /mirror/$scriptmirror

	#==============================================================
	#==============================================================
	#==============================================================
	#NOTE: Si la machine distante ne dispose pas d'un annuaire LDAP synchro avec l'annuaire du serveur SE3
	#      la restauration des ACL sur le poste distant n'est pas possible à ce stade.
	#      Elle devra être assurée lors du redémarrage sur le disque miroir.
	#
	#echo "ssh root@$IPDISTANT \"cd /mirror/part_home/ && setfacl --restore=part_home_list_acls.txt && cd / && umount /dev/$PARTHOME_CIBLE\"" >> /mirror/$scriptmirror

	# J'ai choisi de placer le part_home_list_acls.txt dans /root/ par sécurité, mais on pourrait conserver celui de /home/

	#Avec /root/.profile, il est nécessaire de se loguer une fois en root sur le disque miroir une fois remis en disque principal:
	#echo "ssh root@$IPDISTANT \"cp -f /mirror/part_home/part_home_list_acls.txt /root/ && if ! cat /root/.profile | grep part_home_list_acls.txt > /dev/null; then echo 'if [ -e \"/root/part_home_list_acls.txt\" ]; then cd /home; setfacl --restore=/root/part_home_list_acls.txt && rm -f /root/part_home_list_acls.txt;fi' >> /root/.profile;fi && cd / && umount /dev/$PARTHOME_CIBLE\"" >> /mirror/$scriptmirror

	#Avec /etc/init.d/rcS, il ne devrait pas être nécessaire de se loguer:
	#Le script est lancé trop tôt (avant le démarrage d'OpenLDAP).
	#echo "ssh root@$IPDISTANT \"mount -t ext3 /dev/$PARTROOT_CIBLE /mirror/part_root;cp -f /mirror/part_home/part_home_list_acls.txt /mirror/part_root/root/ && if ! cat /mirror/part_root/etc/init.d/rcS | grep part_home_list_acls.txt > /dev/null; then echo 'if [ -e \"/root/part_home_list_acls.txt\" ]; then cd /home; setfacl --restore=/root/part_home_list_acls.txt && rm -f /root/part_home_list_acls.txt;fi' >> /mirror/part_root/etc/init.d/rcS;fi && cd / && umount /dev/$PARTHOME_CIBLE;umount /dev/$PARTROOT_CIBLE\"" >> /mirror/$scriptmirror








	echo '#! /bin/bash

NAME="aclrestore"
DESC="Restauration des ACL au premier démarrage uniquement"
DAEMON="/root/aclrestore.sh"

case "$1" in
  start)
        echo -n "Starting $DESC: $NAME"
        sh $DAEMON
        echo "."
        ;;
  stop)
        echo -n "Stopping $DESC: $NAME "
        echo "."
        ;;
  *)
        N=/etc/init.d/$NAME
        echo "Usage: $N {start|stop}" >&2
        exit 1
        ;;
esac

exit 0
' > $tmp/aclrestore


	echo '#!/bin/bash

if [ -e "/root/part_home_list_acls.txt" ]; then
	cd /home
	setfacl --restore=/root/part_home_list_acls.txt && rm -f /root/part_home_list_acls.txt
fi

if [ -e "/root/part_var_se3_list_acls.txt" ]; then
	cd /var/se3
	setfacl --restore=/root/part_var_se3_list_acls.txt && rm -f /root/part_var_se3_list_acls.txt
fi
' > $tmp/aclrestore.sh


	echo "ssh root@$IPDISTANT \"mount -t ext3 /dev/$PARTROOT_CIBLE $DOSSMIRROR/part_root\"" >> /mirror/$scriptmirror
	echo "scp $tmp/aclrestore root@$IPDISTANT:$DOSSMIRROR/part_root/etc/init.d/" >> /mirror/$scriptmirror
	echo "scp $tmp/aclrestore.sh root@$IPDISTANT:$DOSSMIRROR/part_root/root/" >> /mirror/$scriptmirror

	echo "ssh root@$IPDISTANT \"chmod 755 $DOSSMIRROR/part_root/etc/init.d/aclrestore && chmod 700 $DOSSMIRROR/part_root/root/aclrestore.sh\"" >> /mirror/$scriptmirror

	echo "ssh root@$IPDISTANT \"cd $DOSSMIRROR/part_root/etc/rc2.d/&&ln -s ../init.d/aclrestore ./S99aclrestore\"" >> /mirror/$scriptmirror

	echo "ssh root@$IPDISTANT \"cp -f $DOSSMIRROR/part_home/part_home_list_acls.txt $DOSSMIRROR/part_root/root/\"" >> /mirror/$scriptmirror
	echo "ssh root@$IPDISTANT \"cd / && umount /dev/$PARTHOME_CIBLE\"" >> /mirror/$scriptmirror

	echo "ssh root@$IPDISTANT \"umount /dev/$PARTROOT_CIBLE\"" >> /mirror/$scriptmirror
	#==============================================================
	#==============================================================
	#==============================================================
fi
echo "" >> /mirror/$scriptmirror


#partition /var/se3
echo 'echo "Montage de la partition VAR/SE3" '>> /mirror/$scriptmirror
if [ "$DDLOCAL" = "1" ]; then
	echo "mount -t xfs /dev/$PARTVARSE3_CIBLE /mirror/part_varse3" >> /mirror/$scriptmirror
else
	echo "ssh root@$IPDISTANT \"mount -t xfs /dev/$PARTVARSE3_CIBLE $DOSSMIRROR/part_varse3\"" >> /mirror/$scriptmirror
fi

echo -e 'if [ $? != 0 ]; then' >> /mirror/$scriptmirror
echo -e "echo \"** ERREUR ** lors du montage de la partition /var/se3 de $DISK2\"  | tee -a \$FICHIERLOG " >> /mirror/$scriptmirror
echo -e 'echo "Le script ne peut se poursuivre." | tee -a $FICHIERLOG '>> /mirror/$scriptmirror
echo -e "echo \"VÉRIFIEZ LE BON FONCTIONNEMENT DU $DISK2\" | tee -a \$FICHIERLOG " >> /mirror/$scriptmirror
echo -e "touch /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"Rapport de fonctionnement du script de mirroring lors de son lancement:\" > /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"Voici les problèmes constatés:\" >> /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "echo \"\" >> /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e 'cat $FICHIERLOG >> /mirror/mail_alerte.txt' >> /mirror/$scriptmirror
echo -e "mail $MAIL_ADMIN -s \"[Alerte /mirror/$scriptmirror] Pb avec le script\" < /mirror/mail_alerte.txt" >> /mirror/$scriptmirror
echo -e "exit 1" >> /mirror/$scriptmirror
echo -e "fi" >> /mirror/$scriptmirror

echo 'echo  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
echo 'echo "rsync de la partition /var/se3" | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
echo 'echo  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
if [ "$DDLOCAL" = "1" ]; then
	#echo 'rsync -av --delete /var/se3/* /mirror/part_varse3 | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	echo '/usr/bin/rsync -av --delete /var/se3/* /mirror/part_varse3 | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	echo "cd /var/se3" >> /mirror/$scriptmirror
	echo "echo \"Sauvegarde / Restauration des ACLS en cours pour /var/se3...\"" >> /mirror/$scriptmirror
	echo "getfacl -R . > /mirror/part_varse3/list_acls.txt" >> /mirror/$scriptmirror
	echo "cd /mirror/part_varse3/" >> /mirror/$scriptmirror
	echo "setfacl --restore=list_acls.txt"  >> /mirror/$scriptmirror
	echo "cd /" >> /mirror/$scriptmirror
	echo "umount /dev/$PARTVARSE3_CIBLE " >> /mirror/$scriptmirror
else
	#echo 'rsync -e ssh -av --delete /var/se3/* root@'$IPDISTANT':'$DOSSMIRROR'/part_varse3  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror
	echo '/usr/bin/rsync -e ssh --rsync-path=/usr/bin/rsync -av --delete /var/se3/* root@'$IPDISTANT':'$DOSSMIRROR'/part_varse3  | tee -a $FICHIERLOG' >> /mirror/$scriptmirror

	echo "cd /var/se3" >> /mirror/$scriptmirror
	echo "echo \"Sauvegarde / Restauration des ACLS en cours pour /var/se3...\"" >> /mirror/$scriptmirror
	echo "getfacl -R . > /mirror/part_var_se3_list_acls.txt" >> /mirror/$scriptmirror

	echo "scp /mirror/part_var_se3_list_acls.txt root@$IPDISTANT:$DOSSMIRROR/part_varse3/" >> /mirror/$scriptmirror

	#==============================================================
	#==============================================================
	#==============================================================
	#NOTE: Si la machine distante ne dispose pas d'un annuaire LDAP synchro avec l'annuaire du serveur SE3
	#      la restauration des ACL sur le poste distant n'est pas possible à ce stade.
	#      Elle devra être assurée lors du redémarrage sur le disque miroir.
	#
	#echo "ssh root@$IPDISTANT \"cd /mirror/part_varse3/ && setfacl --restore=part_var_se3_list_acls.txt && cd / && umount /dev/$PARTVARSE3_CIBLE\"" >> /mirror/$scriptmirror

	# J'ai choisi de placer le part_var_se3_list_acls.txt dans /root/ par sécurité, mais on pourrait conserver celui de /var/se3/

	#Avec /root/.profile, il est nécessaire de se loguer une fois en root sur le disque miroir une fois remis en disque principal:
	#echo "ssh root@$IPDISTANT \"cp -f /mirror/part_varse3/part_var_se3_list_acls.txt /root/ && if ! cat /root/.profile | grep part_var_se3_list_acls.txt > /dev/null; then echo 'if [ -e \"/root/part_var_se3_list_acls.txt\" ]; then cd /var/se3; setfacl --restore=/root/part_var_se3_list_acls.txt && rm -f /root/part_var_se3_list_acls.txt;fi' >> /root/.profile;fi && cd / && umount /dev/$PARTVARSE3_CIBLE\"" >> /mirror/$scriptmirror

	#Avec /etc/init.d/rcS, il ne devrait pas être nécessaire de se loguer:
	#Le script est lancé trop tôt (avant le démarrage d'OpenLDAP).
	#echo "ssh root@$IPDISTANT \"mount -t ext3 /dev/$PARTROOT_CIBLE /mirror/part_root;cp -f /mirror/part_varse3/part_var_se3_list_acls.txt /mirror/part_root/root/ && if ! cat /mirror/part_root/etc/init.d/rcS | grep part_var_se3_list_acls.txt > /dev/null; then echo 'if [ -e \"/root/part_var_se3_list_acls.txt\" ]; then cd /var/se3; setfacl --restore=/root/part_var_se3_list_acls.txt && rm -f /root/part_var_se3_list_acls.txt;fi' >> /mirror/part_root/etc/init.d/rcS;fi && cd / && umount /dev/$PARTVARSE3_CIBLE;umount /dev/$PARTROOT_CIBLE\"" >> /mirror/$scriptmirror



	echo "ssh root@$IPDISTANT \"mount -t ext3 /dev/$PARTROOT_CIBLE $DOSSMIRROR/part_root\"" >> /mirror/$scriptmirror

	echo "ssh root@$IPDISTANT \"cp -f $DOSSMIRROR/part_varse3/part_var_se3_list_acls.txt $DOSSMIRROR/part_root/root/\"" >> /mirror/$scriptmirror
	echo "ssh root@$IPDISTANT \"cd / && umount /dev/$PARTVARSE3_CIBLE\"" >> /mirror/$scriptmirror

	echo "ssh root@$IPDISTANT \"umount /dev/$PARTROOT_CIBLE\"" >> /mirror/$scriptmirror

	#==============================================================
	#==============================================================
	#==============================================================
fi



echo "echo -e '\033[1;35m'" >> /mirror/$scriptmirror
echo "echo '***********'" >> /mirror/$scriptmirror
echo "echo '* Terminé *'" >> /mirror/$scriptmirror
echo "echo '***********'" >> /mirror/$scriptmirror
echo "echo -e '\033[0;37m'" >> /mirror/$scriptmirror



# Création du script mount_disk2
if [ "$DDLOCAL" = "1" ]; then
	scriptmountdisk2="mount_$DISK2.sh"
else
	scriptmountdisk2="mount_${IPDISTANT}_$DISK2.sh"
fi

touch /mirror/$scriptmountdisk2
chmod 700 /mirror/$scriptmountdisk2
echo "mount -t ext3 /dev/$PARTROOT_CIBLE  $DOSSMIRROR/part_root" > /mirror/$scriptmountdisk2
if [ "$PARTVAR" != "aucune" ]; then
        echo "mount -t ext3 /dev/$PARTVAR_CIBLE  $DOSSMIRROR/part_var" >> /mirror/$scriptmountdisk2
fi
echo "mount -t xfs /dev/$PARTHOME_CIBLE $DOSSMIRROR/part_home" >> /mirror/$scriptmountdisk2
echo "mount -t xfs /dev/$PARTVARSE3_CIBLE $DOSSMIRROR/part_varse3" >> /mirror/$scriptmountdisk2

if [ "$DDLOCAL" = "2" ]; then
	scp /mirror/$scriptmountdisk2 root@$IPDISTANT:$DOSSMIRROR/
	ssh root@$IPDISTANT "chmod 700 $DOSSMIRROR/$scriptmountdisk2"
fi




# Création du script umount_disk2
touch /mirror/$scriptumountdisk2
chmod 700 /mirror/$scriptumountdisk2
echo "umount /dev/$PARTROOT_CIBLE" > /mirror/$scriptumountdisk2
echo "umount /dev/$PARTHOME_CIBLE" >> /mirror/$scriptumountdisk2
echo "umount /dev/$PARTVARSE3_CIBLE" >> /mirror/$scriptumountdisk2
if [ "$PARTVAR" != "aucune" ]; then
        echo "umount /dev/$PARTVAR_CIBLE" >> /mirror/$scriptumountdisk2

fi

if [ "$DDLOCAL" = "2" ]; then
	scp /mirror/$scriptumountdisk2 root@$IPDISTANT:$DOSSMIRROR/
	ssh root@$IPDISTANT "chmod 700 $DOSSMIRROR/$scriptumountdisk2"
fi





# Création du script install_lilo_disk2
touch /mirror/$scriptinstalllilodisk2
chmod 700 /mirror/$scriptinstalllilodisk2
echo "cp $DOSSMIRROR/lilo2.conf $DOSSMIRROR/part_root/lilo2.conf" > /mirror/$scriptinstalllilodisk2
#echo "lilo -r $DOSSMIRROR/part_root -C lilo2.conf" >> /mirror/$scriptinstalllilodisk2
echo "/sbin/lilo -r $DOSSMIRROR/part_root -C lilo2.conf" >> /mirror/$scriptinstalllilodisk2
echo "mv $DOSSMIRROR/$scriptinstalllilodisk2 $DOSSMIRROR/${scriptinstalllilodisk2}_sav" >> /mirror/$scriptinstalllilodisk2

if [ "$DDLOCAL" = "2" ]; then
	scp /mirror/$scriptinstalllilodisk2 root@$IPDISTANT:$DOSSMIRROR/
	ssh root@$IPDISTANT "chmod 700 $DOSSMIRROR/$scriptinstalllilodisk2"
fi



# Création du fichier de conf de lilo pour le 2e disque
if [ "$DDLOCAL" = "1" ]; then
	fichlilo2="lilo2.conf"
else
	fichlilo2="lilo2_${IPDISTANT}.conf"
fi
touch /mirror/$fichlilo2
chmod 700 /mirror/$fichlilo2
echo "lba32" > /mirror/$fichlilo2
echo "disk=/dev/$DISK2" >> /mirror/$fichlilo2
echo "bios=0x80" >> /mirror/$fichlilo2
cat /etc/lilo.conf | sed -e '/^$/d' | sed -e '/^#/ d' | sed -e "/lba32/ d" | sed -e "/disk=\/dev.*/ d" | sed -e "/bios=.*/ d" | sed -e "s/boot=\/dev\/$DISK1/boot=\/dev\/$DISK2/" >>  /mirror/$fichlilo2

if [ "$DDLOCAL" = "2" ]; then
	scp /mirror/$fichlilo2 root@$IPDISTANT:$DOSSMIRROR/
	ssh root@$IPDISTANT "if [ -e \"$DOSSMIRROR/lilo2.conf\" ]; then rm -f $DOSSMIRROR/lilo2.conf ;fi && mv $DOSSMIRROR/$fichlilo2 $DOSSMIRROR/lilo2.conf && chmod 700 $DOSSMIRROR/lilo2.conf"
fi



echo -e "$COLTXT"
echo "Fin de la création des scripts."

POURSUIVRE



###################### fin de la création des scripts ###############################

clear

echo -e "$COLPARTIE"
echo "*******"
echo "* FIN *"
echo "*******"

echo -e "$COLINFO"
echo "Script d'installation terminé !!!"
echo ""
echo ""
echo "Le script d'installation a généré trois scripts dans /mirror !"
echo "Le script principal rsync /mirror/$scriptmirror"
echo
echo "Un script /mirror/$scriptmountdisk2 vous permettant de monter les partitions"
echo "de $DISK2 dans les sous repertoires de /mirror afin d'en visualiser le contenu."
echo
echo "Un script /mirror/$scriptumountdisk2 vous permettant de démonter vos partitions"
echo "de $DISK2 dans les sous repertoires de /mirror"
echo

if [ "$DDLOCAL" = "2" ]; then
	echo "Comme vous avez optez pour un mirroring vers un poste distant,"
	echo "ce sont les scripts $scriptmountdisk2 et $scriptumountdisk2 situés"
	echo "sur la machine $IPDISTANT que vous devrez exécuter si le besoin"
	echo "d'accès au données mirrorées se fait sentir."
fi

REPONSE=""
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
        echo -e "$COLTXT"
	echo "Voulez-vous lancer le script rsync de suite "
	echo -e "afin d'effectuer une première synchronisation des disques? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c"
	read REPONSE
done

if [ "$REPONSE" = "o" ]; then
        echo -e "$COLTXT"
	echo "Script lancé !"
        echo -e "$COLCMD"
	cd /mirror/
	./$scriptmirror
fi
exit 0

