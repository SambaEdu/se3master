#!/bin/bash

# **********************************************************
# Installation de SambaEdu3
# Auteur: Olivier LECLUSE
# Ce script est diftribué selon les termes de la licence GPL
# **********************************************************

# Adaptation pour lenny keyser - Mars 2010
# Activation mode debug
# Ajout test carte rezo != eth0
# modif PHPINI="/etc/php5/apache2/php.ini"
# modif conf ldap pour utiliser script mkSlapdCfonf.sh
# LDAPGRP="root" averifier
# modif mrtg
# modif $INITDAPACHE

#$Id: install_se3_.sh 3911 2009-05-15 07:28:41Z gnumdk $

# todo
# déplacer la section clamav dans le postinst se3-clamav

#mode debug
#touch /root/debug
if [ -e /root/debug ]; then
set -x
fi
# cp -f /etc/sudoers.se3 /etc/sudoers


#Init annuaire et mise en place DB_CONFIG de se3
/etc/init.d/slapd stop
cp -a /var/lib/ldap/DB_CONFIG /root/DB_CONFIG_squeeze.ori
rm -rf /var/lib/ldap/*
cp /var/cache/se3_install/conf/DB_CONFIG /var/lib/ldap/ 
chown openldap:openldap /var/lib/ldap/*

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

clear
echo -e "$COLTITRE"
echo "*************************"
echo "* SCRIPT D'INSTALLATION *"
echo "*     DE SAMBAEDU3      *"
echo "*************************"

# Il ne faut pas poursuivre l'installation si aucune carte réseau n'a été détectée:
# Dans ce cas, un "ifconfig eth0" renvoie:
# eth0: error fetching interface information: Device not found
# Détection de la configuration ip sur la 1ere carte réseau

echo -e "$COLCMD\c "
ECARD=$(/sbin/ifconfig | grep eth | sort | head -n 1 | cut -d " " -f 1)
if [ -z "$ECARD" ]; then
ECARD=$(/sbin/ifconfig -a | grep eth | sort | head -n 1 | cut -d " " -f 1)

	if [ -z "$ECARD" ]; then
		echo -e "$COLERREUR"
		echo "Aucune carte réseau n'a été détectée."
		echo "Il n'est pas souhaitable de poursuivre l'installation."
		echo -e "$COLTXT"
		echo -e "Voulez-vous ne pas tenir compte de cet avertissement (${COLCHOIX}1${COLTXT}),"
		echo -e "ou préférez-vous interrompre le script d'installation (${COLCHOIX}2${COLTXT})"
		echo -e "et corriger le problème avant de relancer ce script?"
		REPONSE=""
		while [ "$REPONSE" != "1" -a "$REPONSE" != "2" ]
		do
			echo -e "${COLTXT}Votre choix: [${COLDEFAUT}2${COLTXT}] ${COLSAISIE}\c "
			read REPONSE
	
			if [ -z "$REPONSE" ]; then
				REPONSE=2
			fi
		done
		if [ "$REPONSE" = "2" ]; then
			echo -e "$COLINFO"
			echo "Pour résoudre ce problème, chargez le pilote approprié."
			echo "ou alors complétez le fichier /etc/modules.conf avec une ligne du type:"
			echo "   alias eth0 <nom_du_module>"
			echo -e "Il conviendra ensuite de rebooter pour prendre en compte le changement\nou de charger le module pour cette 'session' par 'modprobe <nom_du_module>"
			echo -e "Vous pourrez relancer ce script via la commande:\n   /var/cache/se3_install/install_se3.sh"
			echo -e "$COLTXT"
			exit 1
		fi
	else
	cp /etc/network/interfaces /etc/network/interfaces.orig
	sed -i "s/eth[0-9]/$ECARD/" /etc/network/interfaces
	ifup $ECARD
	fi

fi
IPADDR=$(/sbin/ifconfig $ECARD | grep inet |cut -d : -f 2 |cut -d \  -f 1| head -n 1)
#
# Détection de la distribution
#

#clear
echo -e "$COLTXT"
echo "Détection de la distribution:"

if [ -e /etc/debian_version ]; then
	echo -e "$COLINFO\c "
	echo "Debian détectée, félicitation ;-)"
	echo -e "$COLCMD\c "
	DISTRIB="DEB"
	WWWPATH="/var/www"
	if [ ! -d /usr/lib/cgi-binse ]; then
		mkdir /usr/lib/cgi-binse
	fi
	CGIPATH="/usr/lib/cgi-binse"
	APACHE="www-se3"
	LDAPGRP="root"
	SMBCONF="/etc/samba/smb.conf"
	APACHECONF="/etc/apache/httpdse.conf"
	SLAPDIR="ldap"
	SCHEMADIR="\/etc\/ldap\/schema"
	SLAPDCONF="/etc/$SLAPDIR/slapd.conf"
	PAMLDAPCONF="/etc/pam_ldap.conf"
	NSSLDAPCONF="/etc/libnss-ldap.conf"
	LDAPSECRET="/etc/ldap.secret"
	NSSWITCH="/etc/nsswitch.conf"
	INITDSAMBA="/etc/init.d/samba"
	SMBCONFIN="smb_3.conf.in"
	INITDAPACHE="/etc/init.d/apache2se"
	INITDSLAPD="/etc/init.d/slapd"
	INITDNSCD=""
	PHPINI="/etc/php5/apache2/php.ini"
	MRTGCFG="/etc/mrtg.cfg"
else
	echo -e "$COLERREUR"
	echo "Distribution non supportée."
	echo -e "$COLTXT"
	exit 1
fi

DEBVER=`cat /etc/debian_version`
echo -e "$COLINFO\c "
case $DEBVER in
    5.0*)
	echo "Debian lenny detectee."
	;;
    6.0*)
	echo "Debian squeeze detectee."
	;;
    7.*)
	echo "Debian wheezy detectee."
	;;
    *)
	echo "Version Debian inconnue"
	;;
esac
#if [ "$DEBVER" = "5.0.4" ]; then
#	echo "Debian lenny détectée."
#fi


################# Detection de la disquette de conf auto ############

if [ -e /etc/se3/setup_se3.data ]; then
	echo -e "${COLTXT}Un script de configuration automatique a été détecté .... ;) "
	chmod 700 /etc/se3/setup_se3.data
	. /etc/se3/setup_se3.data # le "." permet d'inclure le script et ses variables
	# correction pass mysql
	MYSQLPW=$(echo "$MYSQLPW" | sed -e 's/\-//g' | sed -e s'/\$//g' | sed -e 's/\#//g'| sed -e 's/\~//g'| sed -e 's/\&//g')
else
	[ ! -d /etc/se3 ] && mkdir /etc/se3
fi

# // il n'y a pas de depots volatile sous squeeze //
## correction sources.list si besoin pour se3-clamav
#[ -z "$(cat /etc/apt/sources.list| grep "clamav derniere version")" ] && echo "# entree pour clamav derniere version
#deb http://ftp2.de.debian.org/debian-volatile lenny/volatile main" >> /etc/apt/sources.list

#
# Mise en place de l'interface web
#
if [ "$IFACEWEB_AUTOCONF" = "yes" ]; then
	rep="y"
	echo -e "$COLPARTIE"
	echo "Section 1: "
	echo "---------- "
	echo -e "$COLTXT"
	echo "Installation Automatique de l'interface web ....."
else
	echo -e "$COLPARTIE"
	echo "Section 1: "
	echo "---------- "

	echo -e "${COLTXT}Voulez-vous installer l'interface web ? (${COLDEFAUT}O/n${COLTXT}) $COLSAISIE\c "
	read rep
fi
if [ ! "$rep" = "n" ]; then
	echo -e "$COLTXT"
	echo "Installation de l'interface"
	echo -e "$COLCMD\c "
	useradd -u 599 www-se3 -d /var/remote_adm -s /bin/bash
	adduser www-se3 lpadmin

	# Effacement de la config nut
	/bin/rm -Rf /etc/nut

	# modif de /etc/profile afin que la machine passe par un proxy
	if [ ! -z "$PROXY_AUTOCONF" ]; then
		if [ "$PROXY_AUTOCONF" = "yes" ]; then
			echo "#se3_proxy" >> /etc/profile
			echo "http_proxy=\"http://$IPPROXY:$PROXYPORT\"" >> /etc/profile
			echo "ftp_proxy=\"http://$IPPROXY:$PROXYPORT\"" >> /etc/profile
			echo "export http_proxy ftp_proxy" >> /etc/profile
		fi
	else
		if [ ! `cat /etc/profile | grep se3_proxy` ]; then

			REPONSE=""
			while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
			do
				echo -e "$COLTXT"
				echo "Passez-vous par un SLIS ou un autre proxy"
				echo -e "pour vous connecter à internet ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c "
				read REPONSE
			done

			### on ne touche à rien si il n'y a pas de proxy ####
			if [ "$REPONSE" = "o" ]; then
				while [ "$IPOK" != "o" ]
				do
					GW=$(route -n | grep UG | head -n 1 | awk -F' ' '{ print $2 }')
					echo -e "${COLTXT}Quelle est l'adresse ip de votre proxy ? [${COLDEFAUT}${GW}${COLTXT}] ${COLSAISIE}\c "
					read IPPROXY
					[ -z "$IPPROXY" ] && IPPROXY="$GW" 
					echo -e "$COLTXT\c "
					echo -e "Quel est le port de votre proxy?  [${COLDEFAUT}3128${COLTXT}] ${COLSAISIE}\c "
					read PROXYPORT
					if [ -z "$PROXYPORT" ]; then
						PROXYPORT=3128
					fi
					echo -e "$COLTXT\c "
					echo -e "Votre proxy est accessible à ${COLINFO}${IPPROXY}:${PROXYPORT}${COLTXT}"
					IPOK=""
					while [ "$IPOK" != "o" -a "$IPOK" != "n" ]
					do
						echo -e "$COLTXT\c "
						echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c "
						read IPOK
					done
				done

				### on part du principe où le port est PROXYPORT

				echo -e "$COLTXT\c "
				echo "Je mets à jour le fichier /etc/profile ..."
				echo -e "$COLCMD\c "
				echo "#se3_proxy" >> /etc/profile
				echo "http_proxy=\"http://$IPPROXY:$PROXYPORT\"" >> /etc/profile
				echo "ftp_proxy=\"http://$IPPROXY:$PROXYPORT\"" >> /etc/profile
				echo "export http_proxy ftp_proxy" >> /etc/profile
				echo
			fi
		else
		echo -e "$COLINFO\c "
		echo "Un proxy est déjà déclaré dans /etc/profile"
		fi
	fi
fi
#
# Configuration de SambaEdu
#

echo -e "$COLPARTIE"
echo "Section 2: "
echo "---------- "
echo -e "$COLTXT\c "
if [ "$CONFSE3" = "yes" ]; then
	rep="y"
else
	echo -e "Voulez-vous configurer SambaEdu3 ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c "
	read rep
fi
if [ ! "$rep" = "n" ]; then

	if [ "$CONFSE3" != "yes" ]; then
		echo -e "$COLTXT"
		echo -e "Entrez l'adresse IP du serveur SE3 [${COLDEFAUT}$IPADDR${COLTXT}] $COLSAISIE\c "
		read SE3IP
		if [ "$SE3IP" = "" ]; then
			SE3IP=$IPADDR
		fi

		echo -e "$COLTXT"
		echo -e "Entrez le nom DNS complet du serveur SE3 [${COLDEFAUT}`hostname -f`${COLTXT}]"
		echo -e "$COLSAISIE\c "
		read FQHN
		if [ "$FQHN" = "" ]; then
			FQHN=`hostname -f`
		fi
		SERVNAME=`echo $FQHN |cut -d . -f 1`
		DOMNAME=`echo $FQHN |cut -d . -f 2-`

		echo -e "$COLTXT"
		echo -e "Entrez l'adresse de la base MySQL [${COLDEFAUT}localhost${COLTXT}] $COLSAISIE\c "
		read MYSQLIP
		if [ "$MYSQLIP" = "" ]; then
			MYSQLIP="localhost"
		fi

		echo -e "$COLTXT"
		echo -e "Entrez le mot de passe root MySQL ou appuyez simplement sur entrée pour en générer un aléatoirement"
		echo -e 'Attention les caractères "#", "~", "-", "&" et "$" sont interdits et seront supprimés le cas échéant.'
		echo -e "$COLSAISIE\c "
		read MYSQLPW
		MYSQLPW=$(echo "$MYSQLPW" | sed -e 's/\-//g' | sed -e s'/\$//g' | sed -e 's/\#//g'| sed -e 's/\~//g'| sed -e 's/\&//g')

		SE3PW=""
		while [ -z "$SE3PW" ]
		do
			echo -e "$COLTXT"
			echo -e "Entrez le mot de passe Administrateur SambaEdu3 (Non trivial SVP)" 
			echo -e 'Attention les caractères "#", "~", "-", "&" et "$" sont interdits et seront supprimés le cas échéant'
			echo -e "$COLSAISIE\c "
			read SE3PW
			SE3PW=$(echo "$SE3PW" | sed -e 's/\-//g' | sed -e s'/\$//g' | sed -e 's/\#//g'| sed -e 's/\~//g'| sed -e 's/\&//g')
		done
	fi
	if [ -z "$MYSQLPW" ]; then
		echo -e "vous n'avez pas saisi de mot de passe root MySQL, celui-ci va être généré aléatoirement"
		MYSQLPW=`date | md5sum | cut -c 1-6 | sed -e s'/\-//g' | sed -e s'/\$//g' | sed -e 's/\#//g'| sed -e 's/\~//g'| sed -e 's/\&//g'`
	fi
	echo -e "$COLCMD\c "
	mysqladmin password $MYSQLPW 2>/dev/null && echo -e "${COLINFO}Le mot de passe root MySQL a été initialisé à $MYSQLPW"
	echo "[client]">/root/.my.cnf
	echo "password=$MYSQLPW">>/root/.my.cnf
	echo "user=root">>/root/.my.cnf
	chmod 600 /root/.my.cnf

	# Configuration de l'interface
	echo -e "$COLCMD\c "
	sed -e "s/#MYSQLIP#/$MYSQLIP/g;s/#SE3DBPASS#/$SE3PW/g" /var/www/se3/includes/config.inc.php.in > /var/www/se3/includes/config.inc.php
	chmod 660 /var/www/se3/includes/config.inc.php
        chown $APACHE /var/www/se3/includes/config.inc.php
	sed -e "s/#MYSQLIP#/$MYSQLIP/g;s/#SE3DBPASS#/$SE3PW/g" /var/cache/se3_install/conf/SeConfig.ph.in > /etc/SeConfig.ph
	chmod 660 /etc/SeConfig.ph
	chown $APACHE /etc/SeConfig.ph



	# Configuration des droits sur se3/setup
	echo "AuthUserFile $WWWPATH/se3/setup/.htpasswd" >> $WWWPATH/se3/setup/.htaccess
	htpasswd -bc $WWWPATH/se3/setup/.htpasswd admin $SE3PW
	chmod 750 $WWWPATH/se3/setup -R
	chown $APACHE.root $WWWPATH/se3/setup -R

	if [ "$CONFSE3" = "yes" ]; then
		rep="y"
	else
		echo -e "$COLTXT"
		echo -e "Voulez-vous initialiser la Base de Données ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c "
		read rep
	fi
	if [ ! "$rep" = "n" ]; then
	# Initialisation de la Base de Données
		echo -e "$COLCMD\c "
		echo "DROP DATABASE se3db" | mysql -h $MYSQLIP se3db -u root -p$MYSQLPW 2>/dev/null
		mysqladmin create se3db -u root -p$MYSQLPW
		mysql -h $MYSQLIP se3db -u root -p$MYSQLPW <se3db.sql
		echo "DELETE FROM user WHERE User = 'se3db_admin'"|mysql mysql -u root -p$MYSQLPW
		echo "DELETE FROM db WHERE User = 'se3db_admin'"|mysql mysql -u root -p$MYSQLPW
		echo "INSERT INTO user ( \`Host\` , \`User\` , \`Password\` , \`Select_priv\` , \`Insert_priv\` , \`Update_priv\` , \`Delete_priv\` , \`Create_priv\` , \`Drop_priv\` , \`Reload_priv\` , \`Shutdown_priv\` , \`Process_priv\`, \`File_priv\` , \`Grant_priv\` , \`References_priv\` , \`Index_priv\` , \`Alter_priv\` ) VALUES (\"$SE3IP\",'se3db_admin',PASSWORD(\"$SE3PW\"),'Y','Y','Y','Y','Y','Y','N','N','N','N','N','N','N','N')"|mysql mysql -u root -p$MYSQLPW
		echo "INSERT INTO user ( \`Host\` , \`User\` , \`Password\` , \`Select_priv\` , \`Insert_priv\` , \`Update_priv\` , \`Delete_priv\` , \`Create_priv\` , \`Drop_priv\` , \`Reload_priv\` , \`Shutdown_priv\` , \`Process_priv\`, \`File_priv\` , \`Grant_priv\` , \`References_priv\` , \`Index_priv\` , \`Alter_priv\` ) VALUES (\"localhost\",'se3db_admin',PASSWORD(\"$SE3PW\"),'Y','Y','Y','Y','Y','Y','N','N','N','N','N','N','N','N')"|mysql mysql -u root -p$MYSQLPW
		echo "INSERT INTO db  ( \`Host\` , \`Db\` , \`User\` , \`Select_priv\` , \`Insert_priv\` , \`Update_priv\` , \`Delete_priv\` , \`Create_priv\` , \`Drop_priv\` , \`Grant_priv\` , \`References_priv\` , \`Index_priv\` , \`Alter_priv\` ) VALUES (\"$SE3IP\",'se3db','se3db_admin','Y','Y','Y','Y','Y','Y','N','N','N','Y')"|mysql mysql -u root -p$MYSQLPW
		echo "INSERT INTO db ( \`Host\` , \`Db\` , \`User\` , \`Select_priv\` , \`Insert_priv\` , \`Update_priv\` , \`Delete_priv\` , \`Create_priv\` , \`Drop_priv\` , \`Grant_priv\` , \`References_priv\` , \`Index_priv\` , \`Alter_priv\` ) VALUES (\"localhost\",'se3db','se3db_admin','Y','Y','Y','Y','Y','Y','N','N','N','Y')"|mysql mysql -u root -p$MYSQLPW
		mysqladmin reload -u root -p$MYSQLPW
		# Mise a  jours de paramètres en fonction de la distrib
		echo "UPDATE params SET value=\"$WWWPATH/se3\" WHERE name=\"path_to_wwwse3\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$SMBCONF\" WHERE name=\"path2smbconf\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"/etc/$SLAPDIR/ldap.conf\" WHERE name=\"path2ldapconf\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$PAMLDAPCONF\" WHERE name=\"path2pamldapconf\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$NSSLDAPCONF\" WHERE name=\"path2nssldapconf\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$LDAPSECRET\" WHERE name=\"path2ldapsecret\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$INITDSAMBA\" WHERE name=\"serv_samba\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$INITDAPACHE\" WHERE name=\"serv_apache\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$INITDSLAPD\" WHERE name=\"serv_slapd\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$INITDNSCD\" WHERE name=\"serv_nscd\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		# Paramètres divers renseignés par l'utilisateur
		echo "UPDATE params SET value=\"$DOMNAME\" WHERE name=\"domain\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW

		if [ "$MYSQL_AUTOCONF" = "yes" ]; then
			echo "UPDATE params SET value=\"$URL_IFACEWEB\" WHERE name=\"urlse3\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			echo "UPDATE params SET value=\"$DEFAULTGID\" WHERE name=\"defaultgid\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			echo "UPDATE params SET value=\"$UIDPOLICY\" WHERE name=\"uidPolicy\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			echo "UPDATE params SET value=\"$DEFAULTSHELL\" WHERE name=\"defaultshell\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			echo "UPDATE params SET value=\"$URL_MAJSE3\" WHERE name=\"urlmaj\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			echo "UPDATE params SET value=\"$FTP_MAJSE3\" WHERE name=\"ftpmaj\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW

		else
			echo "UPDATE params SET value=\"http://$FQHN:909\" WHERE name=\"urlse3\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		fi

	fi

	if [ "$rep" = "n" ]; then
		echo -e "$COLTXT"
		echo -e "Voulez-vous configurer l'annuaire LDAP ? (${COLCHOIX}O/n${COLTXT}) ${COLSAISIE}\c "
		read rep
	fi
	if [ ! "$rep" = "n" ]; then
		if [ "$CONFSE3" != "yes" ]; then
			# Configuration de l'annuaire LDAP

			rep="o"
			while [ "$rep" = "o" ]
			do
				echo -e "$COLTXT"
				echo -e "Entrez l'adresse IP de l'annuaire LDAP [${COLDEFAUT}$SE3IP${COLTXT}] ${COLSAISIE}\c "
				read LDAPIP
				if [ "$LDAPIP" == "" ]; then
					LDAPIP="$SE3IP"
				fi
				
				if [ "$LDAPIP" != "$SE3IP" ]; then
					while [ "$REP_CONFIRM" != "o" -a "$REP_CONFIRM" != "n" ]
					do
						echo -e "$COLINFO\c "
						echo -e "Vous avez demandé à installer le serveur ldap sur une machine distante."
						echo -e "IL EST INDISPENSABLE QUE LA MACHINE DISTANTE SOIT SOUS DEBIAN LENNY OU QUE L'ANNUAIRE" 
						echo -e "DISTANT SOIT AU FORMAT SCHEMA CHECK ON POUR QUE L'INSTALLATION ABOUTISSE !"
						echo -e "par exemple LCS LENNY répond à ce prérequis" 
						echo -e "Dans le doute, il est vivement recommandé de laisser l'annuaire en local"
						echo -e "Etes vous certain de vouloir conserver  votre choix ? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
						read REP_CONFIRM 
					done
					if [ "$REP_CONFIRM" != "o" ]; then
						echo "L'annuaire sera installé en local"
						LDAPIP="$SE3IP"
					fi

				fi
				#Vérification de la présence de $LDAPIP
				if ! ping -c1 -t1 $LDAPIP > /dev/null; then
					echo -e "${COLERREUR}"
					echo "ERREUR:"
					echo -e "${COLTXT}   Il semble que l'adresse ${COLINFO}$LDAPIP${COLTXT} ne réponde pas au ping."
					echo "   Est-ce que:"
					echo "      - C'est normal ? Les pings sont-ils bloqués ?"
					echo "      - Il y a un problème de connectique?"
					echo "      - Vous vous êtes trompé sur l'adresse?"
					echo "   Si les pings ne sont pas filtrés, il est recommandé de refaire un tour"
					echo "   dans la boucle de saisie de l'adresse pour contrôler qu'après correction"
					echo "   le ping passe bien."
					echo -e "Voulez-vous corriger l'adresse? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c "
					read rep

					if [ -z "$rep" ]; then
						rep="o"
					fi
				else
					rep="n"
				fi
			done

			#Choix du DN de base:
			BDN="dc=`echo $DOMNAME |sed -e "s/\./,dc=/g"`"

			echo -e "$COLINFO"
			echo "Plusieurs formes de DN de BASE sont proposés:"

			echo -e "   ${COLTXT}(${COLCHOIX}1${COLTXT}) Dans l'Académie de Caen, le DN de base serait:\n       $BDN"

			ETABBDN=$(echo $DOMNAME | cut -d"." -f1)
			ACADBDN=$(echo $DOMNAME | cut -d"." -f2)
			ALTBDN2="ou=$ETABBDN,ou=$ACADBDN,ou=education,o=gouv,c=fr"
			echo -e "   ${COLTXT}(${COLCHOIX}2${COLTXT}) Dans l'Académie de Rouen, le DN de base serait:\n       $ALTBDN2"

			echo -e "   ${COLTXT}(${COLCHOIX}3${COLTXT}) Autre format."
			REPONSE=""
			while [ "$REPONSE" != "1" -a "$REPONSE" != "2" -a "$REPONSE" != "3" ]
			do
				echo -e "$COLTXT\c "
				echo -e "Votre choix: $COLSAISIE\c "
				read REPONSE
			done

			case $REPONSE in
				1)
					BDN="$BDN"
				;;
				2)
					BDN="$ALTBDN2"
				;;
				*)
					BDN="$BDN"
				;;
			esac

			rep="o"
			while [ "$rep" = "o" ]
			do
				echo -e "$COLTXT"
				echo -e "Entrez le DN de base [${COLDEFAUT}$BDN${COLTXT}]"
				echo -e "BASEDN: $COLSAISIE\c "
				read BASEDN
				if [ "$BASEDN" = "" ]; then
					BASEDN=$BDN
				fi

				echo -e "$COLTXT"
				echo -e "Entrez le RDN de l'administrateur de l'annuaire [${COLDEFAUT}cn=admin${COLTXT}] $COLSAISIE\c "
				read ADMINRDN
				if [ "$ADMINRDN" = "" ]; then
					ADMINRDN="cn=admin"
				fi

				echo -e "$COLTXT"
				echo -e "Entrez le mot de passe de l'administrateur de l'annuaire [${COLDEFAUT}$SE3PW${COLTXT}] $COLSAISIE\c "
				read ADMINPW
				if [ "$ADMINPW" = "" ]; then
					ADMINPW="$SE3PW"
				fi

				# Test de contact de l'annuaire sous l'identité saisie:
				if [ "$LDAPIP" != "$SE3IP" ]; then
					echo -e "$COLCMD"
					#if ! ldapsearch -xLLL -h $LDAPIP $ADMINRDN > /dev/null; then
					if ! ldapsearch -xLLL -h $LDAPIP -b $BASEDN > /dev/null; then
						echo -e "${COLERREUR}"
						echo "ERREUR:"
						echo -e "$COLTXT\c "
						echo -e "   Il semble que l'annuaire sur ${COLINFO}${LDAPIP}${COLTXT} n'ait pas pu être consulté"
						echo -e "   en anonyme."
						echo -e "   L'adresse IP du LDAP et le BASEDN sont-ils corrects?"
						REPONSE=""
						while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
						do
							echo -e "$COLTXT"
							echo -e "Voulez-vous corriger votre saisie? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
							read REPONSE
						done
					else
						# Pas d'erreur en anonyme
						REPONSE="n"
					fi
					if [ "$REPONSE" = "n" ]; then
						echo -e "$COLCMD\c "
						#if ! ldapsearch -xLLL -h $LDAPIP -D "$ADMINRDN,$BASEDN" -w $ADMINPW $ADMINRDN > /dev/null; then
						if ! ldapsearch -xLLL -h $LDAPIP -D "$ADMINRDN,$BASEDN" -w $ADMINPW -b $BASEDN > /dev/null; then
							echo -e "${COLERREUR}"
							echo "ERREUR:"
							echo -e "$COLTXT\c "
							echo -e "   Il semble que l'annuaire sur ${COLINFO}${LDAPIP}${COLTXT} n'ait pas pu être consulté"
							echo -e "   en se présentant comme administrateur du LDAP"
							echo -e "   (problème de compte/mot de passe?)."
							echo ""
							rep=""
							while [ "$rep" != "o" -a "$rep" != "n" ]
							do
								echo -e "$COLTXT"
								echo -e "Voulez-vous corriger votre saisie? (${COLCHOIX}o/n${COLTXT}) $COLSAISIE\c "
								read rep
							done
						else
							rep="n"
						fi
					else
						# Correction à faire.
						# Un tour de plus dans la boucle.
						rep="o"
					fi
				else
					# L'annuaire est encore vide.
					# Il va être mis en place sur le SE3 lui-même:
					rep="n"
				fi
			done
			PEOPLERDN="People"
			GROUPSRDN="Groups"
			COMPUTERSRDN="Computers"
			PARCSRDN="Parcs"
			RIGHTSRDN="Rights"
			PRINTERSRDN="Printers"
			TRASHRDN="Trash"

 		fi


		echo -e "$COLCMD\c "
		# Renseignement de la base de données à partir des entrées fournies
		echo "UPDATE params SET value=\"$LDAPIP\" WHERE name=\"ldap_server\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$BASEDN\" WHERE name=\"ldap_base_dn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$ADMINRDN\" WHERE name=\"adminRdn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$ADMINPW\" WHERE name=\"adminPw\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"ou=$PEOPLERDN\" WHERE name=\"peopleRdn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"ou=$GROUPSRDN\" WHERE name=\"groupsRdn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"ou=$COMPUTERSRDN\" WHERE name=\"computersRdn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"ou=$PARCSRDN\" WHERE name=\"parcsRdn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"ou=$RIGHTSRDN\" WHERE name=\"rightsRdn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"ou=$PRINTERSRDN\" WHERE name=\"printersRdn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"ou=$TRASHRDN\" WHERE name=\"trashRdn\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
	fi
fi

# Lecture des paramètres LDAP depuis la base de données
# au cas où la section 2 aurait été zappée

if [ "$MYSQLIP" = "" ]; then
	echo -e "$COLTXT"
	echo -e "Entrez l'adresse de la base MySQL [${COLDEFAUT}localhost${COLTXT}] $COLSAISIE\c "
	read MYSQLIP
	if [ "$MYSQLIP" = "" ]; then
		MYSQLIP="localhost"
	fi

	echo -e "$COLTXT"
	echo -e "Entrez le mot de passe Administrateur SambaEdu3 (Non trivial SVP)"
	echo -e "SE3PW: $COLSAISIE\c "
	read SE3PW
fi
echo -e "$COLCMD"
LDAPIP=`echo "SELECT value FROM params WHERE name='ldap_server'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`

DN_BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N | cut -d',' -f1`
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
GROUPSRDN=`echo "SELECT value FROM params WHERE name='groupsRdn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
RIGHTSRDN=`echo "SELECT value FROM params WHERE name='rightsRdn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
COMPUTERSRDN=`echo "SELECT value FROM params WHERE name='computersRdn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
PARCSRDN=`echo "SELECT value FROM params WHERE name='parcsRdn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
PRINTERSRDN=`echo "SELECT value FROM params WHERE name='printersRdn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
TRASHRDN=`echo "SELECT value FROM params WHERE name='trashRdn'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`

echo $ADMINPW >$LDAPSECRET

# Mise en place de la versionSE3

VERSION=`cat version`
#echo "INSERT INTO params VALUES ('', 'version', '$VERSION', 0, 'No version SambaEdu', 4)" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
REJ=`echo "SELECT value FROM params WHERE name='registred'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
if [ -z "$REJ" ]; then
	echo "INSERT INTO params VALUES ('', 'registred', '0', 0, 'Enregistrement du serveur', 4)" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
fi
if [ "$SMBCONFIN" = "smb_3.conf.in" ]; then
	echo "INSERT INTO params VALUES ('', 'smbversion', 'samba3', 0, 'version de Samba', 6)" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
fi

if [ "$SLAPD_AUTOCONF" = "yes" ]; then
	echo -e "$COLPARTIE"
	echo "Section 3: "
	echo "---------- "
	echo -e "$COLTXT\c "
	echo -e "Configuration automatique de SLAPD sur le serveur SE3...."
	rep="y"
else
	echo -e "$COLTITRE\c "
	echo "Récapitulatif de la configuration LDAP"
	echo "--------------------------------------"
	echo -e "$COLINFO\c "
	echo "Adresse de l'annuaire: $LDAPIP"
	echo "DN de l'administrateur: $ADMINRDN,$BASEDN ($ADMINPW)"
	echo "Branche People: $PEOPLERDN,$BASEDN"
	echo "Branche Groups: $GROUPSRDN,$BASEDN"
	echo "Branche Computers: $COMPUTERSRDN,$BASEDN"
	echo "Branche Parcs: $PARCSRDN,$BASEDN"
	echo "Branche Rights: $RIGHTSRDN,$BASEDN"
	echo "Branche Printers: $PRINTERSRDN,$BASEDN"
	echo "Branche Trash: $TRASHRDN,$BASEDN"
	echo -e "$COLTXT\c "
	echo -e "Ces renseignements sont-ils corrects ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c "
	read rep
	if [ "$rep" = "n" ]; then
		echo -e "$COLTXT"
		exit 1
	fi

	#
	# Paramétrage du serveur LDAP sur le serveur SE3
	#

	echo -e "$COLPARTIE"
	echo "Section 3: "
	echo "---------- "
	echo -e "$COLTXT\c "
	echo -e "Voulez-vous configurer le service SLAPD sur le serveur SE3 ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c "
	read rep
fi

if [ ! "$rep" = "n" ]; then
# Configuration du slapd
	echo -e "$COLCMD\c "
	$INITDSLAPD stop
	if [ ! -d /var/lib/ldap ]; then
		mkdir /var/lib/ldap
		chown openldap:openldap /var/lib/ldap
	else
		cp -a /var/lib/ldap /var/lib/ldap.se3sav
	fi
	/usr/share/se3/scripts/mkSlapdConf.sh installinit
# 	cat conf/$SLAPDCONFIN | sed -e "s/#SCHEMADIR#/$SCHEMADIR/g" | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#ADMINRDN#/$ADMINRDN/g" | sed -e "s/#ADMINPW#/$ADMINPW/g" > $SLAPDCONF

	# Prise en compte des lignes spécifiques à la version 2.1.x backportée
	
# 	chmod 640 $SLAPDCONF
# 	chown root.$LDAPGRP $SLAPDCONF
	# pour bypasser le nouveau mode de conf slapd
	mv /etc/ldap/slapd.d /etc/ldap/slapd.d.se3
	/usr/sbin/slapindex 2>/dev/null
	chown openldap.openldap /var/lib/ldap/*
	$INITDSLAPD start
	if [ "$?" != "0" ]; then
		echo -e "$COLERREUR Erreur lors du lancement de ldap - impossible de poursuivre"
		echo -e "$COLTXT\c "
		exit 1
	fi
	sleep 2
	echo "UPDATE params SET value=\"/etc/$SLAPDIR/slapd.conf\" WHERE name=\"path2slapdconf\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
fi

#
# Ecriture dans l'annuaire des branches et entrées nécessaires à SambaEdu3
#
if [ "$SLAPD_AUTOCONF" = "yes" ]; then
	rep="y"
	echo -e "$COLPARTIE"
	echo "Section 4: "
	echo "---------- "
	echo -e "$COLTXT\c "
	echo -e "Intégration des données dans l'annuaire...."
else
	echo -e "$COLPARTIE"
	echo "Section 4: "
	echo "---------- "
	echo -e "$COLTXT\c "
	echo -e "Voulez-vous intégrer les entrées SambaEdu3 à l'annuaire ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c "
	read rep_1T
fi


if [ ! "$rep_1T" = "n" ]; then
	echo -e "$COLCMD\c "
	# Suppression des ou= dans les noms des branches
	ADMINR=`echo $ADMINRDN |cut -d , -f 1 |cut -d = -f 2`
	PEOPLER=`echo $PEOPLERDN |cut -d = -f 2`
	GROUPSR=`echo $GROUPSRDN |cut -d = -f 2`
	COMPUTERSR=`echo $COMPUTERSRDN |cut -d = -f 2`
	PARCSR=`echo $PARCSRDN |cut -d = -f 2`
	RIGHTSR=`echo $RIGHTSRDN |cut -d = -f 2`
	PRINTERSR=`echo $PRINTERSRDN |cut -d = -f 2`

	# Lecture de defaultgid pour attribution a admin

	defaultgid=`echo "SELECT value FROM params WHERE name='defaultgid'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`

	# test le type de BASEDN
	cp ldif/top.ldif /tmp/
	TYPE_DN=`echo $DN_BASEDN | cut -d = -f 1`
	if [ "$TYPE_DN" = "ou" ]
	then
		echo "ObjectClass: organizationalUnit" >> /tmp/top.ldif
	fi
	if [ "$TYPE_DN" = "dc" ]
	then
		echo "ObjectClass: Domain" >> /tmp/top.ldif
	fi

	DN_DC=`echo $DN_BASEDN | cut -d = -f 2`
	echo "$TYPE_DN: $DN_DC" >> /tmp/top.ldif

	# Mise en place des entrées dans l'annuaire
	cat /tmp/top.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/People.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#PEOPLE#/$PEOPLER/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/cnadmin.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#ADMINRDN#/$ADMINRDN/g" | sed -e "s/#ADMINR#/$ADMINR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/Groups.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/admins.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/lcs-users.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/Eleves.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/Profs.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/Administration.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/machines.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/Computers.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#COMPUTERS#/$COMPUTERSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/Parcs.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#PARCS#/$PARCSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/RightsRoot.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#RIGHTS#/$RIGHTSR/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/rights.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#RIGHTS#/$RIGHTSR/g" | sed -e "s/#GROUPS#/$GROUPSR/g"| sed -e "s/#PEOPLE#/$PEOPLER/g" | ldapadd -x -c -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	cat ldif/printers.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#PRINTERS#/$PRINTERSR/g" | ldapadd -x -c -D "$ADMINRDN,$BASEDN" -w $ADMINPW
fi

#
# Mise en place de Samba, enfin...
#
if [ ! -z "$(ldapsearch -xLLL sambaDomainName=* sambaDomainName | 
grep '^sambaDomainName: ' | sed -e 's/^sambaDomainName: //')" ]; then 
	echo "AVERTISSEMENT: ..."
	echo "Un Domaine déjà existant dans l'annuaire a été trouvé:"
	ldapsearch -xLLL sambaDomainName=* sambaDomainName | 
grep '^sambaDomainName: ' | sed -e 's/^sambaDomainName: //'
	TAB_DEFAULT_DOMAIN=($(ldapsearch -xLLL sambaDomainName=* sambaDomainName | 
grep '^sambaDomainName: ' | sed -e 's/^sambaDomainName: //'))
	DEFAULT_DOMAIN=${TAB_DEFAULT_DOMAIN[0]}
else
	DEFAULT_DOMAIN="SAMBAEDU3"
fi


if [ "$SMB_AUTOCONF" = "yes" ]; then
	rep="y"
	echo -e "$COLPARTIE"
	echo "Section 5: "
	echo "---------- "
	echo -e "$COLTXT\c "
	echo -e "Configuration automatique de Samba ...."
else

	echo -e "$COLPARTIE"
	echo "Section 5: "
	echo "---------- "
	echo -e "$COLTXT\c "
	echo -e "Voulez-vous configurer Samba ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c "
	read rep
fi


if [ ! "$rep" = "n" ]; then
	if [ "$SMB_AUTOCONF" != "yes" ]; then

		# Configuration du samba
		echo -e "$COLTXT"
		echo -e "Entrez le nom du domaine NT [${COLDEFAUT}${DEFAULT_DOMAIN}${COLTXT}] $COLSAISIE\c "
		read NTDOM
		if [ "$NTDOM" = "" ]; then
			NTDOM="$DEFAULT_DOMAIN"
		fi

		echo -e "$COLTXT"
		echo -e "Entrez le nom netbios du serveur SE3 [${COLDEFAUT}se3${COLTXT}] $COLSAISIE\c "
		read NETBIOS
		if [ "$NETBIOS" = "" ]; then
			NETBIOS="se3"
		fi

		if [ "$SE3IP" = "" ]; then
			echo -e "$COLTXT"
			echo -e "Entrez l'adresse IP du serveur SE3 [${COLDEFAUT}$IPADDR${COLTXT}] $COLSAISIE\c "
			read SE3IP
			if [ "$SE3IP" = "" ]; then
				SE3IP=$IPADDR
			fi
		fi


		echo -e "$COLTXT"
		echo -e "Entrez le nom de votre interface reseau [${COLDEFAUT}${ECARD}${COLTXT}] $COLSAISIE\c "
		read SE3ECARD
		
		NETMASK=`/sbin/ifconfig $ECARD |grep inet |cut -d \  -f 16 | cut -d : -f 2`
		echo -e "$COLTXT"
		echo -e "Entrez le masque de sous-réseau [${COLDEFAUT}$NETMASK${COLTXT}] $COLSAISIE\c "
		read SE3MASK
		if [ "$SE3MASK" = "" ]; then
			SE3MASK=$NETMASK
		fi
	fi
	
	if [ "$SE3ECARD" = "" ]; then
		SE3ECARD="$ECARD"
	fi

if [ "$NTDOM" != "$DEFAULT_DOMAIN" -a "$DEFAULT_DOMAIN" != "SAMBAEDU3" ]; then
ldapdelete -x -D $ROOTDN -w $PASSDN sambaDomainName=$sambaDomainName,$BASEDN
fi

	echo -e "$COLTXT"
	echo "Mise en place de la configuration Samba..."
	echo -e "$COLCMD\c "
	$INITDSAMBA stop
	CHARSET="UTF-8"
	sed -e "s/#IPSERVEUR#/$SE3IP/g;s/#MASK#/$SE3MASK/g;s/#NETBIOSNAME#/$NETBIOS/g;s/#DOMAIN#/$NTDOM/g;s/#SLAPDIP#/$LDAPIP/g;s/#BASEDN#/$BASEDN/g;s/#ADMINRDN#/$ADMINRDN/g;s/#ADMINPW#/$ADMINPW/g;s/#PEOPLE#/$PEOPLERDN/g;s/#GROUPS#/$GROUPSRDN/g;s/#COMPUTERS#/$COMPUTERSRDN/g;s/#CHARSET#/$CHARSET/g" conf/$SMBCONFIN > $SMBCONF
	smbpasswd -w $ADMINPW

	echo -e "$COLTXT"
	echo "Démarrage du serveur Samba..."
	echo -e "$COLCMD\c "
	$INITDSAMBA start
	# Renseignement du SID du domaine
	# Et Renseignement des comptes root et admin samba
	echo -e "$COLTXT"
	echo "Renseignement du SID du domaine, des comptes root et admin Samba..."
	echo -e "$COLCMD\c "
	let gid1=2*$defaultgid+1001
	DOMAINSID=`net getlocalsid 2>/dev/null | cut -d: -f2 | sed -e "s/ //g"`
	if [ ! "$rep_1T" = "n" ]; then
		cat ldif/root.ldif | sed -e "s/#BASEDN#/$BASEDN/g" > ldif/root.ldif.2
		cat ldif/admin.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#PEOPLE#/$PEOPLER/g" | sed -e "s/#DOMAIN#/$DOMNAME/g" | sed -e "s/#DEFAULTGID#/$defaultgid/g" | sed -e "s/#GID1#/$gid1/g" >>ldif/root.ldif.2
		/usr/share/se3/sbin/convertSambaAccount --sid $DOMAINSID --input ldif/root.ldif.2 --output /root/root.ldif.3
		ldapadd -x -c -D "$ADMINRDN,$BASEDN" -w $ADMINPW -f /root/root.ldif.3
		sed -e "s/#BASEDN#/$BASEDN/g;s/#DOMAINSID#/$DOMAINSID/g;s/#GROUPS#/$GROUPSR/g;s/#PEOPLE#/$PEOPLERDN/g" ldif/Samba.ldif > /root/Samba.ldif
		ldapadd -x -c -D "$ADMINRDN,$BASEDN" -w $ADMINPW -f /root/Samba.ldif
		
		echo -e "$COLTXT"
		echo "Mappage des groupes..."
		echo -e "$COLCMD\c "
		# ajout dbo pour mappage des groupes de bases
		net groupmap add sid=$DOMAINSID-512 ntgroup=Admins unixgroup=admins type=domain comment="Administrateurs du domaine"
		net groupmap add ntgroup=Eleves unixgroup=Eleves type=domain comment="Eleves du domaine"
		net groupmap add ntgroup=Profs unixgroup=Profs type=domain comment="Profs du domaine"
		net groupmap add ntgroup="Utilisateurs du domaine" rid="513" unixgroup="lcs-users" type="domain"
		net groupmap add ntgroup="machines" rid="515" unixgroup="machines" type="domain"

		echo "UPDATE params SET value=\"$NTDOM\" WHERE name=\"se3_domain\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$NETBIOS\" WHERE name=\"netbios_name\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$SE3IP\" WHERE name=\"se3ip\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$SE3MASK\" WHERE name=\"se3mask\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$SE3ECARD\" WHERE name=\"ecard\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW

	fi
	/usr/share/se3/sbin/userChangePwd.pl admin $SE3PW
	DS=`echo "SELECT value FROM params WHERE name='domainsid'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
	if [ -z "$DS" ]; then
		echo "INSERT INTO params VALUES ('', 'domainsid', \"$DOMAINSID\", 0, 'SID du domaine Samba', 4)" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
	else
		echo "UPDATE params SET value=\"$DOMAINSID\" WHERE name=\"domainsid\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
	fi

	# Saisie de l'entrée du PDC dans Computers
	echo -e "$COLTXT"
	echo "Ajout de l'entrée du PDC dans la branche Computers..."
	echo -e "$COLCMD\c "
	COMPUTERSR=`echo $COMPUTERSRDN |cut -d = -f 2`
	LDAP_MASTER=`ldapsearch -xLLL -b $COMPUTERSRDN,$BASEDN "(&(l=maitre)("'!'"(ipHostnumber=$SE3IP)))"`

	if [ -z "$LDAP_MASTER" ]; then
		cat ldif/se3pdc.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#COMPUTERS#/$COMPUTERSR/g" | sed -e "s/#NETBIOSNAME#/$NETBIOS/g" | sed -e "s/#IPSERVEUR#/$SE3IP/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
	else
		cat ldif/se3pdc.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#COMPUTERS#/$COMPUTERSR/g" | sed -e "s/#NETBIOSNAME#/$NETBIOS/g" | sed -e "s/#IPSERVEUR#/$SE3IP/g" | sed -e "s/maitre/secondaire/" | sed -e "s/Maitre/Secondaire/" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW
		echo "${COLINFO}Attention, il y a déjà un serveur déclaré maitre dans l'annuaire. Se3 sera enregistré comme serveur secondaire dans le ldap"
		echo "$COLTXT"
	fi

	#relancer le serveur cups
	echo -e "$COLTXT"
	echo "Redémarrage de CUPS..."
	echo -e "$COLCMD\c "
	[ -f /etc/init.d/cupsys ] && /etc/init.d/cupsys restart
	[ -f /etc/init.d/cups ] && /etc/init.d/cups restart

	# install trombine

  #
 
  # Génération clé rsa root
	if [ ! -e /root/.ssh/id_rsa.pub ]; then
	    ssh-keygen -t rsa -N "" -f /root/.ssh/id_rsa -q
	fi
	cp /root/.ssh/id_rsa.pub /var/se3/Progs/install/installdll
  
	
fi

# keyser ajout demandé par le ploufon - pour la sauvegarde de secret.tdb
cp -a /var/lib/samba/private/secrets.tdb /etc/save/

# keyser modif du 4/4/05 - Config en fonction des serveur LCS / SLIS

if [ "$LCS_OU_SLIS" = "yes" ]; then

	echo -e "$COLPARTIE"
	echo "Section 6: "
	echo "---------- "
	echo -e "$COLTXT\c "
	echo -e "Configuration automatique du positionnement de l'IP du SLIS ou LCS dans MySQL...\c "
	if [ ! -z "$SLIS_IP" ]; then
		echo "UPDATE params SET value=\"$SLIS_IP\" WHERE name=\"slisip\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"$SLIS_IP\" WHERE name=\"ntpserv\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		echo "UPDATE params SET value=\"http://${SLIS_IP}:2000\" WHERE name=\"slis_url\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
	else
		if [ ! -z "$SLIS_LCS" ]; then
			echo "UPDATE params SET value=\"$LCS_IP\" WHERE name=\"lcsip\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		fi
	fi

else

	echo -e "$COLPARTIE"
	echo "Section 6: "
	echo "---------- "
	echo -e "$COLTXT\c "
	echo -e "Présence éventuelle de SLIS ou LCS ...\n"
	IPOK="NO"
	REPONSE=""
	while [ "$REPONSE" != "1" -a "$REPONSE" != "2" -a "$REPONSE" != "3" ]
	do
			echo -e "$COLTXT\c "
			echo -e "Quel type de serveur de communication avez-vous sur votre réseau ?\n"
			echo -e "$COLTXT\c "
			echo -e "   ${COLTXT}(${COLCHOIX}1${COLTXT}) Un SLIS\n "
			echo -e "   ${COLTXT}(${COLCHOIX}2${COLTXT}) Un LCS\n "
			echo -e "   ${COLTXT}(${COLCHOIX}3${COLTXT}) Vous n'avez pas de tel serveur de communication\n "
			echo -e "$COLTXT\c "
			echo -e "Votre choix: $COLSAISIE\c "
			read REPONSE

		case $REPONSE in
		1)
			while [ "$IPOK" != "o" ]
			do
				echo -e "${COLTXT}Quelle est l'adresse IP de votre SLIS ? ${COLSAISIE}\c "
				read SLIS_IP
				echo -e "$COLTXT\c "
				echo -e "Votre SLIS a comme IP ${COLINFO}${SLIS_IP}${COLTXT}"
				IPOK=""
				while [ "$IPOK" != "o" -a "$IPOK" != "n" ]
				do
					echo -e "$COLTXT\c "
					echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c "
					read IPOK
				done
			done
			echo -e "$COLTXT\c "
			echo "Ecriture des informations relatives au SLIS dans MySQL..."
			echo -e "$COLCMD\c "
			echo "UPDATE params SET value=\"$SLIS_IP\" WHERE name=\"slisip\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			echo "UPDATE params SET value=\"$SLIS_IP\" WHERE name=\"ntpserv\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			echo "UPDATE params SET value=\"http://${SLIS_IP}:2000\" WHERE name=\"slis_url\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			DEFAULTGID="600"
			DEFAULTSHELL="/dev/null"
			echo "UPDATE params SET value=\"$DEFAULTGID\" WHERE name=\"defaultgid\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
			echo "UPDATE params SET value=\"$DEFAULTSHELL\" WHERE name=\"defaultshell\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		;;

		2)
			while [ "$IPOK" != "o" ]
			do
				echo -e "${COLTXT}Quelle est l'adresse IP de votre LCS ? ${COLSAISIE}\c "
				read LCS_IP
				echo -e "$COLTXT\c "
				echo -e "Votre LCS a comme IP ${COLINFO}${LCS_IP}${COLTXT}"
				IPOK=""
				while [ "$IPOK" != "o" -a "$IPOK" != "n" ]
				do
					echo -e "$COLTXT\c "
					echo -e "Est-ce correct ? (${COLCHOIX}o/n${COLTXT}) ${COLSAISIE}\c "
					read IPOK
				done
			done
			echo -e "$COLTXT\c "
			echo "Ecriture de l'IP de LCS dans MySQL..."
			echo -e "$COLCMD\c "
			echo "UPDATE params SET value=\"$LCS_IP\" WHERE name=\"lcsip\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		;;

		3)
			echo -e "$COLTXT\c "
			echo "Ecriture de l'information dans MySQL..."
			echo -e "$COLCMD\c "
			echo "UPDATE params SET value=\"http://${SE3IP}:909\" WHERE name=\"urlse3\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
		;;

		*)
			echo -e "${COLERREUR}\n"
			echo "Choix erroné. Veuillez recommencer svp."
			echo -e "$COLTXT\c "
		;;

		esac
	done

fi

[ ! -z `getent group | grep "lcs-users:x:108"` ] && /usr/share/se3/sbin/se3_modif_gidNumber.sh

if [ ! -e ${MIGFOLDER} ]; then
	/usr/share/se3/sbin/install_quotas.sh
fi
### ajout keyser  version 1.0 ###

# ajout des chemins de se3 ds le path de root
echo 'PATH="${PATH}:/usr/share/se3/sbin:/usr/share/se3/scripts"' >> /root/.profile

#Fermeture de ssh sauf pour root
touch /etc/nologin

#Passage aux mots de pass cryptés
/usr/sbin/pwconv

# Droit sur rw public est géré via l'interface 
# setfacl -m u::rwx /var/se3/Docs/public
# setfacl -m g::rwx /var/se3/Docs/public
# setfacl -m d:u::rwx /var/se3/Docs/public
# setfacl -m d:g::rwx /var/se3/Docs/public
# setfacl -m d:o::rwx /var/se3/Docs/public
setfacl -m d:o::rwx /var/se3/Progs/rw

# au cas ou il existerait un ancien fichier pref.js, on le vire....
rm  -f /etc/skel/user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js

# Instanciation
echo "Instanciation en cours..."
/usr/share/se3/sbin/instance_se3.sh 

# Relance apache2se
echo -e "$COLCMD"
/etc/init.d/apache2se restart
/etc/init.d/apache2 restart
echo -e "$COLTXT"


# installation script intégration clients linux - deplace ds se3-domain


# installation des zorn tools
echo -e "$COLTITRE"
echo "Configuration du compte adminse3"
echo -e "$COLTXT"
XPPASS=`echo "SELECT value FROM params WHERE name='xppass'" | mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW -N`
#saisir pass si necessaire
# a Faire
if [  -z "$XPPASS" ]; then
	XPPASS_RDM="$(makepasswd)"
	echo -e "${COLTXT}Lors de la jonction au domaine des machines Win 2000/XP, un compte local adminse3 sera créé.\nVeuillez saisir un mot de passe pour ce compte  [${COLDEFAUT}${XPPASS_RDM}${COLTXT}]  ${COLSAISIE}"
	read XPPASS
	echo -e "${COLTXT}"
	[  -z "$XPPASS" ] && XPPASS="$XPPASS_RDM"
	echo "UPDATE params SET value=\"$XPPASS\" WHERE name=\"xppass\""|mysql -h $MYSQLIP se3db -u se3db_admin -p$SE3PW
fi

### Creation adminse3 dans annuaire et mise en place privileges admin-adminse3 pour mise au domaine
/usr/share/se3/sbin/create_adminse3.sh

### remise en place des droits par défaut
/usr/share/se3/scripts/permse3


# actualisation du cache des parametres : 
/usr/share/se3/includes/config.inc.sh -clpbmsdf 

# Lance postinst de nos dependances, ces dernieres ayant besoin de l'execution du script d'install
[ -f /var/lib/dpkg/info/se3-logonpy.postinst ] && /var/lib/dpkg/info/se3-logonpy.postinst configure
[ -f /var/lib/dpkg/info/se3-domain.postinst ] && /var/lib/dpkg/info/se3-domain.postinst configure

#Lancement maj intermediaires si necessaire
cd /var/cache/se3_install/
./maj_se.sh


echo -e "$COLTITRE"
echo "Terminé!"
echo -e "$COLTXT"
