#!/bin/sh

## $Id: se3_upgrade_lenny.sh 3662 2009-04-12 00:01:42Z keyser $ ##

#####Script permettant de migrer un serveur Se3 de Sarge en Etch#####

#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLCMD="\033[1;37m"     # Blanc
COLERREUR="\033[1;31m"  # Rouge
COLTXT="\033[0;37m"     # Gris
COLINFO="\033[0;36m"	# Cyan
COLPARTIE="\033[1;34m"	# Bleu

echo -e "$COLTITRE"
echo "*********************************************"
echo "* Script de migration de Etch vers Lenny    *"
echo "*********************************************"
echo -e "$COLTXT"


sleep 1

[ -e /root/debug ] && DEBUG="yes"
[ -e /root/nodl ] && NODL="yes"

ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$1"
	echo -e "$COLTXT"
	DEBIAN_PRIORITY="high"
	DEBIAN_FRONTEND="dialog" 
	export  DEBIAN_PRIORITY
	export  DEBIAN_FRONTEND
	exit 1
}
POURSUIVRE()
{
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Peut-on poursuivre? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done

	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		ERREUR "Abandon!"
	fi
}

REPORT_FILE="$(tempfile)"
MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail a  l'admin
	echo "$REPORT"  | mail -s "[SE3] Erreurs constatées sur $0" $MAIL_ADMIN
fi
}


LINE_TEST()
{
if ( ! wget -q --output-document=/dev/null 'ftp://wawadeb.crdp.ac-caen.fr/welcome.msg') ; then
	ERREUR "Votre connexion internet ne semble pas fonctionnelle !!" 
	exit 1
fi
}


# GENDISCOVER()
# {
# echo -e "$COLINFO"
# echo "Ajout de /etc/init.d/se3discover pour détection des modules au boot"
# echo -e "$COLCMD"
# cat >/etc/init.d/se3discover <<END
# #!/bin/sh -e
# case "$1" in
# start|restart)
#     discover-modprobe -v
#     ;;
# stop|reload|force-reload)
#     ;;
# esac
# END
# chmod +x /etc/init.d/se3discover
# ln -s /etc/init.d/se3discover /etc/rc2.d/S01se3discover
# }


echo -e "$COLPARTIE"
echo "Préparation et tests du système" 
echo -e "$COLTXT"


DEBIAN_VERSION=`cat /etc/debian_version`
[ "$DEBIAN_VERSION" != "4.0" ] && ERREUR "Ce script doit être lancé sous etch !!!"

LINE_TEST

DEBIAN_PRIORITY="critical"
DEBIAN_FRONTEND="noninteractive"
export  DEBIAN_FRONTEND
export  DEBIAN_PRIORITY
echo -e "$COLINFO"
echo "mise à jour des paquets disponibles....Patientez svp"
echo -e "$COLTXT"
apt-get update >/dev/null || ERREUR "Une erreur s'est produite lors de la mise à jour des paquets disponibles"
echo "Ok !"
SE3_CANDIDAT=$(apt-cache policy se3 | grep "Candidat" | awk '{print $2}')
SE3_INSTALL=$(apt-cache policy se3 | grep "Install" | awk '{print $2}')
[ "$SE3_CANDIDAT" != "$SE3_INSTALL" ] && ERREUR "Il semble que votre serveur n'est pas à jour\nLancez un apt-get install se3 pour le mettre à jour puis relancez le script de migration"

if [ "$NODL" != "yes" ]; then
	echo -e "$COLINFO"
	echo "Vérification en ligne que vous avez bien la dernière version des scripts de migration"
	echo -e "$COLTXT"
	cd /root
	ARCHIVE_FILE="migration_etch2lenny.tgz"
	ARCHIVE_FILE_MD5="migration_etch2lenny.md5"
	SCRIPTS_DIR="/usr/share/se3/sbin"
	
	rm -f $ARCHIVE_FILE_MD5 $ARCHIVE_FILE
	wget -N -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr/majse3/$ARCHIVE_FILE
	wget -N -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr/majse3/$ARCHIVE_FILE_MD5
	MD5_CTRL=$(cat $ARCHIVE_FILE_MD5)
	MD5_CTRL_LOCAL=$(md5sum $ARCHIVE_FILE)
	if [ "$MD5_CTRL" != "$MD5_CTRL_LOCAL" ]
	then	
		echo -e "$COLERREUR"
		echo "Controle MD5 de l'archive incorrecte, relancez le script afin qu'elle soit de nouveau téléchargée"
		echo -e "$COLTXT"
		exit 1
	fi

	tar -xzf $ARCHIVE_FILE
	cd $SCRIPTS_DIR
	MD5_CTRL_LOCAL1=$(md5sum se3_upgrade_lenny.sh)
	# MD5_CTRL_LOCAL3=$(md5sum migration_UTF8.sh)
	cd -
	MD5_CTRL1=$(cat se3_upgrade_lenny.md5)
	MD5_CTRL2=$(cat migration_ldap_lenny.md5)
	# MD5_CTRL3=$(cat migration_UTF8.md5)
	chmod +x *.sh

	[ "$MD5_CTRL1" != "$MD5_CTRL_LOCAL1" ] && RELANCE="YES" && cp se3_upgrade_lenny.sh $SCRIPTS_DIR/
# 	[ "$MD5_CTRL2" != "$MD5_CTRL_LOCAL2" ] && cp migration_ldap_etch.sh $SCRIPTS_DIR/
	# [ "$MD5_CTRL3" != "$MD5_CTRL_LOCAL3" ] && cp migration_UTF8.sh $SCRIPTS_DIR/
	if [ "$RELANCE" == "YES" ]
	then
		echo -e "$COLINFO"
		echo "Les scripts de migration ont été mis à jour depuis le serveur central, veuiller relancer se3_upgrade_lenny.sh"
		echo "afin de prendre en compte les changements"
		exit 1
		echo -e "$COLTXT"
	
	
	fi
	echo -e "$COLINFO"
	echo "Vous disposez de la dernière version des scritps de migration, la migration peut se poursuivre..."
	sleep 2
	echo -e "$COLTXT"
else
echo "mode debug pas de telechargement"
sleep 2
fi

echo -e "$COLPARTIE"
echo "Migration phase 1 : Mise à jour des modules si nécessaire" 
echo -e "$COLTXT"

if [ ! -z "$(dpkg -s se3-dhcp 2>/dev/null | grep "Status: install ok")" ];then
	apt-get install se3-dhcp -y || ERREUR "Une erreur s'est produite lors de la mise à jour du paquet se3-dhcp\n Veuillez résoudre le problème avant de relancer le script de migration"
fi

if [ ! -z "$(dpkg -s se3-clamav 2>/dev/null | grep "Status: install ok")" ]; then
	apt-get install se3-clamav -y || ERREUR "Une erreur s'est produite lors de la mise à jour du paquet se3-clamav\n Veuillez résoudre le problème avant de relancer le script de migration"
fi

if 
[ ! -z "$(dpkg -s se3-clonage 2>/dev/null| grep "Status: install ok")" ]; then
	apt-get install se3-clonage -y || ERREUR "Une erreur s'est produite lors de la mise à jour du paquet se3-clonage\n Veuillez résoudre le problème avant de relancer le script de migration"
fi

if [ ! -z "$(dpkg -s se3-wpkg 2>/dev/null | grep "Status: install ok")" ]; then
	apt-get install se3-dhcp -y || ERREUR "Une erreur s'est produite lors de la mise à jour du paquet se3-wpkg\n Veuillez résoudre le problème avant de relancer le script de migration"
fi

PARTROOT=`df | grep "/\$" | sed -e "s/ .*//"`
PARTROOT_SIZE=$(fdisk -s $PARTROOT)
if [ "$PARTROOT_SIZE" -le 1500000 ]; then
	ERREUR "La partition racine fait moins de 1.5Go, c'est insuffisant pour passer en Etch"
fi


## recuperation des variables necessaires pour interroger mysql ###
WWWPATH="/var/www"
if [ -e "$WWWPATH/se3/includes/config.inc.php" ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut	-d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut	-d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut	-d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible !!"
	echo "le script ne peut se poursuivre"
	exit 1
fi


IP_LOCAL=`ifconfig | grep Bcast |cut -d":" -f2| cut -d" " -f1`
LDAP_SERVER=`echo "SELECT value FROM params WHERE name=\"ldap_server\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`


option="-y"
PERMSE3_OPTION="--light"
DEBIAN_PRIORITY="critical"
DEBIAN_FRONTEND="noninteractive"
export  DEBIAN_FRONTEND
export  DEBIAN_PRIORITY

[ "$DEBUG" != "yes" ] && apt-get clean

USE_SPACE=$(df -h | grep "/var$" | awk '{print $5}' | sed -e s/%//)
[ ! "$USE_SPACE" -le 80 ] && ERREUR "Pas assez de place sur le disque pour lancer la mise à jour"

echo -e "$COLPARTIE"
echo "Partie 3 : Migration en Lenny - installations des paquets prioritaires" 
echo -e "$COLTXT"
POURSUIVRE
# 
# if [ ! -z "$(dpkg -s se3-ocs-clientlinux 2>/dev/null| grep "Status: install ok")" ]; then 
# 	apt-get remove se3-ocs-clientlinux -y --purge 
# 	rm -Rf /etc/ocsinventory-client
# 	rm -f /etc/cron.d/ocsinventory-client
# 	rm -rf usr/share/ocsinventory-NG
# 	rm -f /usr/sbin/ocsinventory-client.pl
# 	rm -f /bin/ocsinv 


mv /etc/apt/sources.list /etc/apt/sources.list_save_migration
echo "# Sources standard:
deb http://ftp.fr.debian.org/debian/ lenny main non-free contrib
deb-src http://ftp.fr.debian.org/debian/ lenny main non-free contrib

# Security Updates:
deb http://security.debian.org/ lenny/updates main contrib non-free

# sources pour se3
deb ftp://wawadeb.crdp.ac-caen.fr/debian etch se3

#### Sources XP désactivee en prod ####
#deb ftp://wawadeb.crdp.ac-caen.fr/debian etch se3XP

# entree pour clamav derniere version
deb http://ftp2.de.debian.org/debian-volatile lenny/volatile main" > /etc/apt/sources.list
	
# 	# On se lance
echo "Dpkg::Options {\"--force-confold\";}" > /etc/apt/apt.conf	
# 	echo "Dpkg::Options {\"--force-confnew\";}" > /etc/apt/apt.conf
echo -e "$COLINFO"
echo "mise à jour des dépots...Patientez svp" 
echo -e "$COLTXT"
apt-get update >/dev/null || ERREUR "Une erreur s'est produite lors de la mise à jour des paquets disponibles"
echo "Ok !"
echo -e "$COLINFO"
# echo "mise à jour de lib6 et des locales" 
# echo -e "$COLTXT"
# echo -e "${COLINFO}Ne pas s'alarmer des erreurs sur les locales, c'est inévitable à cette étape de la migration\n Il est également possible que le noyau en cours se désinstalle, un autre sera installé ensuite$COLTXT"
# apt-get install libc6 locales -y < /dev/tty

# if [ "$?" != "0" ]; then
# mv /etc/apt/sources.list_save_migration /etc/apt/sources.list 
# ERREUR "Une erreur s'est produite lors de la mise à jour des paquets lib6 et locales"
# fi


echo "Arrêt si necessaire du service Backuppc"
/etc/init.d/backuppc stop >/dev/null

echo -e "$COLPARTIE"
echo "Partie 4 : Migration en lenny - installations des paquets restants" 
echo -e "$COLTXT"
POURSUIVRE
echo -e "$COLINFO"
echo "migration du système lancée.....ça risque d'être long ;)" 
echo -e "$COLTXT"
apt-get dist-upgrade $option  < /dev/tty


# if [ "$?" != "0" ]; then
# mv /etc/apt/sources.list_save_migration /etc/apt/sources.list 
# ERREUR "Une erreur s'est produite lors de la mise à jour des paquets lib6 et locales"
# fi

#Install ssmtp si necessaire
apt-get install ssmtp -y >/dev/null 

# # Lien pour logonpl
# [ ! -e /usr/lib/libmysqlclient.so.12 ] && ln -s /usr/lib/libmysqlclient.so.15 /usr/lib/libmysqlclient.so.12

echo -e "$COLINFO"
echo "Fin de la migration du système"
echo -e "$COLTXT"

echo -e "$COLINFO"
echo "Nettoyage de /home/netlogon......Patientez !"
echo -e "$COLTXT"
rm /home/netlogon/*.bat
rm /home/netlogon/*.txt

perl -pi -e "s/etch/lenny/;" /etc/apt/sources.list
echo -e "$COLPARTIE"
echo "Partie 5 : Mise à jour du paquet se3 sous sa version lenny" 
echo -e "$COLTXT"

apt-get update >/dev/null
apt-get install se3 $option --allow-unauthenticated

 Recuperation des params LDAP
#

BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
        echo "Impossible d'accéder au paramètre BASEDN"
        exit 1
fi
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PEOPLERDN" ]; then
        echo "Impossible d'accéder au paramètre PEOPLERDN"
        exit 1
fi
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINRDN" ]; then
        echo "Impossible d'accéder au paramètre ADMINRDN"
        exit 1
fi
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINPW" ]; then
        echo "Impossible d'accéder au paramètre ADMINPW"
        exit 1
fi

# Mise a jour annuaire (suite changement dans samba)
/usr/share/se3/sbin/migrationLenny.sh

# Modification du fichier php.ini

# Modif des paramètres apache pour les scripts de création des comptes
# perl -pi -e 's&;include_path = ".:/usr/share/php"&include_path=".:/var/www/se3/includes"&' /etc/php5/apache2/php.ini
# sed "s/#AddDefaultCharset.*/AddDefaultCharset ISO-8859-1/" -i /etc/apache2se/apache2.conf
# perl -pi -e "s/OCS_MODPERL_VERSION 1/OCS_MODPERL_VERSION 2/" /etc/apache2se/conf.d/ocsinventory.conf

echo "Redémarrage des services...."
echo -e "$COLCMD"
# A désactiver ! utf8 not rulaize !
# perl -pi -e 's&#AddDefaultCharset.*&AddDefaultCharset	UTF8&' /etc/apache2se/apache2.conf
/etc/init.d/apache2se restart
/etc/init.d/mysql restart
/etc/init.d/samba restart


echo -e "$COLINFO"
echo "Terminé !!!"
echo -e "$COLINFO"
echo -e "$COLTXT"

[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_REPORT

rm -f /etc/apt/apt.conf
DEBIAN_PRIORITY="high"
DEBIAN_FRONTEND="dialog" 
export  DEBIAN_PRIORITY
export  DEBIAN_FRONTEND
exit 0
