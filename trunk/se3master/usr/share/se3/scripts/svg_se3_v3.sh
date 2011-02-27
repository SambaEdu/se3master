#!/bin/bash
#
##### Script de sauvegarde de divers param�tres SE3 #####
#
# Auteur : Stephane Boireau (Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# Derni�re modif: 25/05/2006

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
	echo " - de /var/lib/samba"
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

if [ $quitter = "oui" ]; then
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

ladate=$(date +"%Y.%m.%d-%H.%M.%S");
jour=$(date +%a)
semaine=$(date +%V)

if [ $svg_hebdo="oui" ]; then
	if [ -e "$dossier_svg/svg_hebdo/num_semaine.txt" ]; then
		if [ $semaine != $(cat "$dossier_svg/svg_hebdo/num_semaine.txt") ]; then
			echo $semaine > "$dossier_svg/svg_hebdo/num_semaine.txt"
			rm -fr $dossier_svg/svg_hebdo/semaine_${semaine}
			mkdir -p $dossier_svg/svg_hebdo/semaine_${semaine}
			cp -a $dossier_svg/mysql $dossier_svg/svg_hebdo/semaine_${semaine}
			cp -a $dossier_svg/ldap $dossier_svg/svg_hebdo/semaine_${semaine}
			cp -a $dossier_svg/samba $dossier_svg/svg_hebdo/semaine_${semaine}
			cp -a $dossier_svg/etc $dossier_svg/svg_hebdo/semaine_${semaine}
		fi
	else
		if [ -e $dossier_svg/mysql ]; then
			if [ ! -z "$(ls $dossier_svg/mysql)" ]; then
				mkdir -p "$dossier_svg/svg_hebdo"
				echo $semaine > "$dossier_svg/svg_hebdo/num_semaine.txt"
				mkdir -p $dossier_svg/svg_hebdo/semaine_${semaine}
				cp -a $dossier_svg/mysql $dossier_svg/svg_hebdo/semaine_${semaine}
				cp -a $dossier_svg/ldap $dossier_svg/svg_hebdo/semaine_${semaine}
				cp -a $dossier_svg/samba $dossier_svg/svg_hebdo/semaine_${semaine}
				cp -a $dossier_svg/etc $dossier_svg/svg_hebdo/semaine_${semaine}
			fi
		fi
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
			fi
			mysqldump -uroot --default-character-set=latin1 $base > "$dossier_svg/mysql/$base.$jour.sql"
		fi
	done
fi
echo ""

echo -e "$COLTXT"
echo "Sauvegarde de LDAP"
echo -e "$COLCMD\c"
ldapsearch -xLLL -D "cn=admin,$(cat /etc/ldap/ldap.conf | grep '^BASE' | tr '\t' " " | sed -e 's/ \{2,\}/ /g' | cut -d' ' -f2)" -w $(cat /etc/ldap.secret) > "$dossier_svg/ldap/ldap.$jour.ldif"
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
echo "Sauvegarde de /var/lib/samba"
echo -e "$COLCMD\c"
if [ -e "$dossier_svg/samba/var_lib_samba.$jour.tar.gz" ]; then
        rm -f "$dossier_svg/samba/var_lib_samba.$jour.tar.gz"
fi
tar -czf "$dossier_svg/samba/var_lib_samba.$jour.tar.gz" /var/lib/samba
echo ""

echo -e "$COLTXT"
echo "Sauvegarde de /etc"
echo -e "$COLCMD\c"
if [ -e "$dossier_svg/etc/etc.$jour.tar.gz" ]; then
        rm -f "$dossier_svg/etc/etc.$jour.tar.gz"
fi
tar -czf "$dossier_svg/etc/etc.$jour.tar.gz" /etc

chown -R root:root "$dossier_svg"
chmod -R 700 "$dossier_svg"
echo ""

rm -f $fich_lock

echo -e "$COLTITRE"
echo "***********"
echo "* Termin� *"
echo "***********"
echo -e "$COLTXT"

