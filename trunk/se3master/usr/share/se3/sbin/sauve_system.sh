#!/bin/bash
#
##### Script de sauvegarde de divers param�tres SE3 #####
#
# Auteur : Stephane Boireau (Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# Derni�re modif: 09/07/2011

# Chemin des sauvegardes:
#dossier_svg="/home/sauvegardes/fichiers_se3"
dossier_svg="/var/se3/save"

# ===============================
# Volume maximum pour effectuer la sauvegarde de /var/lib/ldap
volume_ldap_max="100"	#### J'AI SUPPRIM� CE CHOIX... C'EST VITE ENORME AVEC SARGE ####
# Si le volume de /var/lib/ldap d�passe 100Mo, on ne fait pas d'archive de /var/lib/ldap
# Seul un export LDIF sera alors effectu�.
# ===============================

# Pour conserver des fichiers de sauvegarde sur une ann�e � raison de une par semaine.
# En mettant 'oui' ci-dessous, 52 dossiers seront g�n�r�s au bout d'une ann�e.
svg_hebdo="non"

if [ -z "$1" -o "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script permettant d'effectuer la sauvegarde:"
	echo " - de l'annuaire LDAP"
	echo " - de /etc"
	echo " - des bases MySQL suivantes: 'se3db' et 'mysql'"
	echo " - de /var/lib/samba ou juste /var/lib/samba/secrets.tdb"
	echo ""
	echo "Usage : - Passer en param�tre \$1 la dur�e de vie en secondes du script de"
	echo "          sauvegarde."
	echo "          Pass� ce d�lai, si le script est relanc�, les t�ches de sauvegarde"
	echo "          pr�c�dentes seront interrompues."
	echo "        - Passer 'conservation_hebdo' en param�tre \$2"
	echo "          pour conserver des exemplaires par semaine sur une ann�e."
	echo "        - Passer 'backuppc' en param�tre \$2 ou \$3"
	echo "          si la sauvegarde est lanc�e par backuppc."
	echo "        - Passer 'forcer' en param�tre \$2 ou \$3"
	echo "          si la sauvegarde doit �tre lanc�e malgr� le fonctionnement"
	echo "          de backuppc."
	echo "        - Passer 'noacl' en param�tre \$2 ou \$3 ou \$4"
	echo "          pour ne pas sauvegarder les ACL de /home et /var/se3"
	echo "        - Passer 'varlibsamba' en param�tre \$2 ou \$3 ou \$4 ou \$5"
	echo "          pour sauvegarder tout /var/lib/samba et pas seulement"
	echo "          le /var/lib/samba/secrets.tdb"
	exit
fi

# On bascule en mode conservation de 52 sauvegardes par an
# en plus du roulement sur 7 jours si le param�tre ci-dessous est pass�:
if [ "$2" = "conservation_hebdo" ]; then
	svg_hebdo="oui"
fi

# Chemin des fichiers de lock:
chemin_lock="/var/lock"

# Nom du fichier de lock:
fich_lock="$chemin_lock/svgse3.lck"

# Valeur TMP:
ladate=$(date +"%Y.%m.%d-%H.%M.%S")



# La sauvegarde peut �tre lanc�e en autonome ou bien via backupc.
# Si backuppc tourne, la sauvegarde autonome est d�sactiv�e.
# Sinon, elle peut �tre lanc�e manuellement ou via une t�che cron.
# Pour lancer la sauvegarde depuis backuppc,
# passer dans les param�tres ($2 ou $3) la chaine 'backuppc'
if echo "$*" | grep "forcer" | grep -v grep > /dev/null; then
	# Le param�tre 'forcer' permet de lancer manuellement la sauvegarde, m�me si backuppc tourne.
	# Ne le faites que si voys �tes s�r de ne pas prvoquer une collision de sauvegardes
	# entre votre sauvegarde manuelle et la sauvegarde backuppc.
	quitter="non"
else
	if echo "$*" | grep "backuppc" | grep -v grep > /dev/null; then
		quitter="non"
	else
		if ps aux | grep backuppc | grep -v grep > /dev/null; then
			quitter="oui"
		fi
	fi
fi

if [ "$quitter" = "oui" ]; then
	exit
fi



# Est-ce que la sauvegarde pr�c�dente est termin�e?
# et/ou s'est d�roul�e normalement?
if [ -e "$fich_lock" ]; then
	t1=$(cat $fich_lock)
	t_expiration=$(($t1+$1))
	t2=$(date +%s)
	difference=$(($t2-$t1))
	heures=$(($difference/3600))
	minutes=$((($difference-3600*$heures)/60))
	secondes=$(($difference-3600*$heures-60*$minutes))
	if [ $t2 -gt $t_expiration ]; then
		echo "Probl�me avec $O" > /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
		echo "T�che initi�e en $t1 et il est $t2 soit ${heures}H${minutes}M${secondes}S." >> /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
		echo "La t�che de sauvegarde a d�pass� le d�lai imparti." | tee -a /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
		echo "Le fichier de lock n'a pas �t� supprim�." | tee -a /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
		echo "Les processus encore en cours vont �tre supprim�s." | tee -a /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt

		if [ $(ps aux | grep $0 | grep -v grep | wc -l) -ge 2 ]; then
			echo "Plusieurs exemplaires du script $0 tournent:" >> /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
			ps aux | grep $0 | grep -v grep >> /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt

			# Probl�me comment identifier le processus courant?
			# $(pidof $0) n'a pas l'air de fonctionner.
			# Dois-je effectuer:
			#rm -f $fich_lock
			#killall $0
			# Je viens de tester, cela ne fonctionne pas...
			# Cela pose un probl�me...
			# Si la premi�re partie de la sauvegarde ne s'arr�te pas et qu'on tue le processus,
			# le script svg_se3... va se poursuivre et lancer la sauvegarde de la partie suivante...
			# Si elle merdouille elle aussi, il peut falloir un certain nombre de tours pour tout purger.
		fi

		# Est-ce que je pourrais me contenter de tuer tous les processus qui touchent � $dossier_svg
		if ps aux | grep $dossier_svg | grep -v "grep " > /dev/null; then
			echo "Liste des processus d�gag�s lors du nettoyage:" >> /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
			ps aux | grep $dossier_svg | grep -v "grep " >> /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
			ps aux | grep $dossier_svg | grep -v "grep " | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2 | while read PID
			do
				echo "kill -9 $PID" | tee -a /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
				kill -9 $PID 2>&1 | tee -a /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
			done

			if ps aux | grep $dossier_svg | grep -v "grep " > /dev/null; then
				echo "Un des processus ne s'est pas arr�t�." | tee -a /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
				echo "On abandonne." | tee -a /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
				quitter="oui"
			else
				echo "Le nettoyage a �t� effectu�, mais il est curieux que le script ait d�pass� le temps imparti pour la sauvegarde." | tee -a /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
				quitter="non"
			fi

			mail_admin=$(ldapsearch -xLLL uid=admin mail | grep "^mail: " | sed -e "s/^mail: //")
			mail_ssmtp=$(grep "^root=" /etc/ssmtp/ssmtp.conf | cut -d"=" -f2)

			if [ ! -z "$mail_admin" ]; then
				mail $mail_admin -s "[Serveur SE3] Probl�me avec le processus de sauvegarde" < /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
			fi

			if [ ! -z "$mail_ssmtp" ]; then
				mail $mail_ssmtp -s "[Serveur SE3] Probl�me avec le processus de sauvegarde" < /root/tmp/rapport_nettoyage_svgse3_${ladate}.txt
			fi

			if [ $quitter = "oui" ]; then
				exit
			fi
		fi

		rm -f $fich_lock
	else
		echo "Une t�che de sauvegarde est d�j� en cours..."
		echo "Elle n'a pas atteint sa dur�e limite autoris�e."
		echo "Veuillez patienter."
		exit
	fi
fi



# Cr�ation d'un fichier de LOCK:
date +%s > $fich_lock


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

echo -e "$COLTITRE"
echo "************************************"
echo "* Sauvegarde des fichiers de conf, *"
echo "*   de l'annuaire, des bases,...   *"
echo "************************************"

echo -e "$COLCMD\c"
mkdir -p "$dossier_svg"
mkdir -p "$dossier_svg/mysql"
mkdir -p "$dossier_svg/ldap"
mkdir -p "$dossier_svg/samba"
mkdir -p "$dossier_svg/etc"
mkdir -p "$dossier_svg/acl"

ladate=$(date +"%Y.%m.%d-%H.%M.%S");
jour=$(date +%a| tr -d ".")
semaine=$(date +%V)

if [ $svg_hebdo = "oui" ]; then
	if [ -e "$dossier_svg/svg_hebdo/num_semaine.txt" ]; then
		if [ $semaine != $(cat "$dossier_svg/svg_hebdo/num_semaine.txt") ]; then
			# C'est une nouvelle semaine qui commence... on met de c�t� la sauvegarde de la semaine pr�c�dente.
			echo $semaine > "$dossier_svg/svg_hebdo/num_semaine.txt"
			# On supprime la sauvegarde de l'ann�e pr�c�dente si elle existe
			if [ -d "$dossier_svg/svg_hebdo/semaine_${semaine}" ]; then
				rm -fr $dossier_svg/svg_hebdo/semaine_${semaine}
			fi
			# et on (re)cr�e le dossier
			mkdir -p $dossier_svg/svg_hebdo/semaine_${semaine}
			#cp -a $dossier_svg/mysql $dossier_svg/svg_hebdo/semaine_${semaine}
			#cp -a $dossier_svg/ldap $dossier_svg/svg_hebdo/semaine_${semaine}
			#cp -a $dossier_svg/samba $dossier_svg/svg_hebdo/semaine_${semaine}
			#cp -a $dossier_svg/etc $dossier_svg/svg_hebdo/semaine_${semaine}

			transfert_hebdo="y"
		else
			transfert_hebdo="n"
		fi
	else
		if [ -e $dossier_svg/mysql ]; then
			# Une sauvegarde au moins a d�j� �t� effectu�e.
			if [ ! -z "$(ls $dossier_svg/mysql)" ]; then
				transfert_hebdo="y"
			else
				transfert_hebdo="n"
			fi
		else
			transfert_hebdo="n"
		fi
	fi


	if [ $transfert_hebdo = "y" ]; then
		mkdir -p $dossier_svg/svg_hebdo/semaine_${semaine}/mysql
		for base in se3db mysql
		do
			liste=($(ls -t $dossier_svg/mysql/$base.*.sql))
			if [ ${#liste[*]} -gt 0 ]; then
				# On r�cup�re la premi�re sauvegarde de la semaine
				cp -a ${liste[0]} $dossier_svg/svg_hebdo/semaine_${semaine}/mysql/
			fi
		done


		mkdir -p $dossier_svg/svg_hebdo/semaine_${semaine}/ldap
		liste=($(ls -t $dossier_svg/ldap/DB_CONFIG.*))
		if [ ${#liste[*]} -gt 0 ]; then
			# On r�cup�re la sauvegarde la plus r�cente de la semaine
			cp -a ${liste[0]} $dossier_svg/svg_hebdo/semaine_${semaine}/ldap/
		fi
		liste=($(ls -t $dossier_svg/ldap/ldap.*.ldif))
		if [ ${#liste[*]} -gt 0 ]; then
			# On r�cup�re la sauvegarde la plus r�cente de la semaine
			cp -a ${liste[0]} $dossier_svg/svg_hebdo/semaine_${semaine}/ldap/
		fi
		if [ -e $dossier_svg/ldap/ldap.se3sav.tar.gz ]; then
			cp -a $dossier_svg/ldap/ldap.se3sav.tar.gz $dossier_svg/svg_hebdo/semaine_${semaine}/ldap/
		fi


		mkdir -p $dossier_svg/svg_hebdo/semaine_${semaine}/samba
		#if ls $dossier_svg/samba/ | grep "var_lib_samba." > /dev/null; then
		test=$(ls $dossier_svg/samba/ | grep 'var_lib_samba\.')
		if [ ! -z "$test" ]; then
			liste=($(ls -t $dossier_svg/samba/var_lib_samba.*.tar.gz))
			if [ ${#liste[*]} -gt 0 ]; then
				# On r�cup�re la sauvegarde la plus r�cente de la semaine
				cp -a ${liste[0]} $dossier_svg/svg_hebdo/semaine_${semaine}/samba/
			fi
		fi
		#if ls $dossier_svg/samba/ | grep "var_lib_samba_secrets_tdb." > /dev/null; then
		test=$(ls $dossier_svg/samba/ | grep 'var_lib_samba_secrets_tdb.')
		if [ ! -z "$test" ]; then
			liste=($(ls -t $dossier_svg/samba/var_lib_samba_secrets_tdb.*))
			if [ ${#liste[*]} -gt 0 ]; then
				# On r�cup�re la sauvegarde la plus r�cente de la semaine
				cp -a ${liste[0]} $dossier_svg/svg_hebdo/semaine_${semaine}/samba/
			fi
		fi

		mkdir -p $dossier_svg/svg_hebdo/semaine_${semaine}/etc
		liste=($(ls -t $dossier_svg/etc/etc.*.tar.gz))
		if [ ${#liste[*]} -gt 0 ]; then
			# On r�cup�re la sauvegarde la plus r�cente de la semaine
			cp -a ${liste[0]} $dossier_svg/svg_hebdo/semaine_${semaine}/etc/
		fi

		# On ne conserve pas dans le roulement les ACL... trop gros

		echo $semaine > "$dossier_svg/svg_hebdo/num_semaine.txt"
	fi
fi

echo -e "$COLTXT"
echo "Sauvegarde de MySQL"
echo -e "$COLCMD\c"
#/etc/init.d/mysql stop
#ls /var/lib/mysql | while read A
#do
#       if [ -d "/var/lib/mysql/$A" ]; then
#                base=$(echo "$A" | sed -e "s!/$!!")
#                if [ -e "$dossier_svg/mysql/$base.$jour.tar.gz" ]; then
#                        rm -f "$dossier_svg/mysql/$base.$jour.tar.gz"
#                fi
#                tar -czf "$dossier_svg/mysql/$base.$jour.tar.gz" /var/lib/mysql/$base
#        fi
#done
#/etc/init.d/mysql start
if [ -e /root/.my.cnf ]; then
	#ls /var/lib/mysql | while read A
	for base in se3db mysql
	do
		#if [ -d "/var/lib/mysql/$A" ]; then
		if [ -d "/var/lib/mysql/$base" ]; then
			#base=$(echo "$A" | sed -e "s!/$!!")
			if [ -e "$dossier_svg/mysql/$base.$jour.sql" ]; then
				rm -f "$dossier_svg/mysql/$base.$jour.sql"
				# sup scories
				rm -f "$dossier_svg/mysql/$base.$jour..sql" 
			fi
			mysqldump -uroot --default-character-set=latin1 $base > "$dossier_svg/mysql/$base.$jour.sql"
		fi
	done
# SINON: ENVOYER UN MAIL D'AVERTISSEMENT... A FAIRE
fi
echo ""

echo -e "$COLTXT"
echo "Sauvegarde de LDAP"
echo -e "$COLCMD\c"
#ldapsearch -xLLL -D "cn=admin,$(cat /etc/ldap/ldap.conf | grep '^BASE' | tr '\t' " " | sed -e 's/ \{2,\}/ /g' | cut -d' ' -f2)" -w $(cat /etc/ldap.secret) > "$dossier_svg/ldap/ldap.$jour.ldif"
rm -f $dossier_svg/ldap/ldap.$jour..ldif
ldapsearch -xLLL -D $(grep ^rootdn /etc/ldap/slapd.conf|tr "\t" " " | sed -e "s/ \{2,\}/ /g" | sed -e "s/'//g"  | sed -e 's/"//g' | cut -d" " -f2) -w $(cat /etc/ldap.secret) > "$dossier_svg/ldap/ldap.$jour.ldif"
#=====================================
test_volume=$(du -sk "$dossier_svg/ldap/ldap.$jour.ldif" | tr "\t" " " | cut -d" " -f1)
if [ "$test_volume" = "0" ]; then
	mail_admin=$(ldapsearch -xLLL uid=admin mail | grep "^mail: " | sed -e "s/^mail: //")
	mail_ssmtp=$(grep "^root=" /etc/ssmtp/ssmtp.conf | cut -d"=" -f2)

	echo "La sauvegarde LDAP g�n�r�e cette nuit est vide.
L'�tat du LDAP doit perturber le fonctionnement du serveur.
Il faut r�agir avant que le roulement sur 7 jours soit achev� sinon vous n'aurez plus de sauvegarde valide de l'annuaire LDAP (sauf si la sauvegarde hebdomadaire est activ�e, auquel cas vous avez un roulement des fichiers sur une ann�e)." > /root/tmp/rapport_pb_ldap_svgse3_${ladate}.txt

	if [ ! -z "$mail_admin" ]; then
		mail $mail_admin -s "[Serveur SE3] Probl�me avec le processus de sauvegarde" < /root/tmp/rapport_pb_ldap_svgse3_${ladate}.txt
	fi

	if [ ! -z "$mail_ssmtp" ]; then
		mail $mail_ssmtp -s "[Serveur SE3] Probl�me avec le processus de sauvegarde" < /root/tmp/rapport_pb_ldap_svgse3_${ladate}.txt
	fi
else
	if [ "$svg_hebdo" != "oui" ]; then
		mkdir -p "$dossier_svg/svg_ldap_secu"
		#ls -t -1 /var/se3/save/ldap/*.ldif | head -n1
		cp "$dossier_svg/ldap/ldap.$jour.ldif" "$dossier_svg/svg_ldap_secu/ldap.$(date +%d).ldif"
	fi
fi
#=====================================
cp -f /var/lib/ldap/DB_CONFIG $dossier_svg/ldap/DB_CONFIG.$jour

#if [ "$(du -sm /var/lib/ldap | tr '\t' ' ' | cut -d' ' -f1)" -lt $volume_ldap_max ]; then
#	/etc/init.d/slapd stop
#	if [ -e "$dossier_svg/ldap/var_lib_ldap.$jour.tar.gz" ]; then
#		rm -f "$dossier_svg/ldap/var_lib_ldap.$jour.tar.gz"
#	fi
#	tar -czf "$dossier_svg/ldap/var_lib_ldap.$jour.tar.gz" /var/lib/ldap
#	/etc/init.d/slapd start
#fi
# Au cas o�, on archive le LDAP vierge:
if [ ! -e "$dossier_svg/ldap/ldap.se3sav.tar.gz" -a -e /var/lib/ldap.se3sav ]; then
	if [ $(du -sk /var/lib/ldap.se3sav/ | tr "\t" " " | cut -d" " -f1) -le 10000 ]; then
		# En principe le dossier fait ~1.5Mo
		tar -czf $dossier_svg/ldap/ldap.se3sav.tar.gz /var/lib/ldap.se3sav
	fi
fi
echo ""

echo -e "$COLTXT"
if echo "$*" | grep "varlibsamba" > /dev/null; then
	echo "Sauvegarde de /var/lib/samba"
	echo -e "$COLCMD\c"
	if [ -e "$dossier_svg/samba/var_lib_samba.$jour.tar.gz" ]; then
			rm -f "$dossier_svg/samba/var_lib_samba.$jour.tar.gz"
			rm -f "$dossier_svg/samba/var_lib_samba.$jour..tar.gz"
	fi
	if [ -e "$dossier_svg/samba/var_lib_samba_secrets_tdb.$jour" ]; then
			rm -f "$dossier_svg/samba/var_lib_samba_secrets_tdb.$jour"
			rm -f "$dossier_svg/samba/var_lib_samba_secrets_tdb..$jour"
	fi
	fi
	tar -czf "$dossier_svg/samba/var_lib_samba.$jour.tar.gz" /var/lib/samba
else
	echo "Sauvegarde de /var/lib/samba/secrets.tdb"
	echo -e "$COLCMD\c"
	if [ -e "$dossier_svg/samba/var_lib_samba.$jour.tar.gz" ]; then
			rm -f "$dossier_svg/samba/var_lib_samba.$jour.tar.gz"
			rm -f "$dossier_svg/samba/var_lib_samba.$jour..tar.gz"
	fi
	if [ -e "$dossier_svg/samba/var_lib_samba_secrets_tdb.$jour" ]; then
			rm -f "$dossier_svg/samba/var_lib_samba_secrets_tdb.$jour"
			rm -f "$dossier_svg/samba/var_lib_samba_secrets_tdb..$jour"
	fi
	cp /var/lib/samba/secrets.tdb "$dossier_svg/samba/var_lib_samba_secrets_tdb.$jour"
fi
echo ""

echo -e "$COLTXT"
echo "Sauvegarde de /etc"
echo -e "$COLCMD\c"
if [ -e "$dossier_svg/etc/etc.$jour.tar.gz" ]; then
        rm -f "$dossier_svg/etc/etc.$jour.tar.gz"
        rm -f "$dossier_svg/etc/etc.$jour..tar.gz"
fi
tar -czf "$dossier_svg/etc/etc.$jour.tar.gz" /etc

if ! echo "$*" | grep noacl > /dev/null; then
	echo -e "$COLTXT"
	echo "Sauvegarde des ACL de /var/se3"
	echo -e "$COLCMD\c"
	if [ -e "$dossier_svg/acl/varse3_acl.bz2" ]; then
		rm -f "$dossier_svg/acl/varse3_acl.bz2"
	fi
	cd /var/se3
	getfacl -R . | bzip2 > $dossier_svg/acl/varse3_acl.bz2
fi

chown -R root:root "$dossier_svg"
chmod -R 700 "$dossier_svg"
echo ""

rm -f $fich_lock

echo -e "$COLTITRE"
echo "***********"
echo "* Termin� *"
echo "***********"
echo -e "$COLTXT"

