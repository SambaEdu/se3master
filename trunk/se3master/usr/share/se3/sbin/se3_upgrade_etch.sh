#!/bin/sh

## $Id$ ##

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
echo "* Script de migration de SARGE vers ETCH     *"
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
if ( ! wget -q --output-document=/dev/null 'ftp://wawadeb.crdp.ac-caen.fr/debian/dists/stable/Release') ; then
	ERREUR "Votre connexion internet ne semble pas fonctionnelle !!" 
	exit 1
fi
}

GENLOCALES()
{
echo -e "$COLINFO"
echo "Modification des locales systeme pour passage en UTF-8 par défaut"
echo -e "$COLCMD"
cat >/etc/locale.gen <<END
fr_FR.UTF-8 UTF-8
fr_FR ISO-8859-1
fr_FR@euro ISO-8859-15
END
cat >/etc/environment <<END
`grep "LANGUAGE" /etc/environment`
LANG="fr_FR.UTF-8"
END
cat >/etc/default/locale <<END
LANG=fr_FR.UTF-8
END
/usr/sbin/locale-gen
}

GENDISCOVER()
{
echo -e "$COLINFO"
echo "Ajout de /etc/init.d/se3discover pour détection des modules au boot"
echo -e "$COLCMD"
cat >/etc/init.d/se3discover <<END
#!/bin/sh -e
case "$1" in
start|restart)
    discover-modprobe -v
    ;;
stop|reload|force-reload)
    ;;
esac
END
chmod +x /etc/init.d/se3discover
ln -s /etc/init.d/se3discover /etc/rc2.d/S01se3discover
}

INSTALLNOYO()
{
echo -e "$COLINFO"
echo "Installation de grub" 
echo -e "$COLTXT"
echo "Le script va remplacer lilo par Grub et configurer automatiquement
le fichier de configuration de boot"
# POURSUIVRE
apt-get install busybox initramfs-tools klibc-utils libklibc grub -y || "Erreur lors de l'installation des paquets nécessaire à grub"
# rm -f /boot/grub/menu.*
# rm -f /boot/grub/device.map
rm -f /boot/vmlinuz
rm -f /boot/initrd.img
grub-install --no-floppy hd0
/usr/sbin/update-grub -y
echo -e "$COLINFO"
echo "Installation du nouveau noyau" 
echo -e "$COLTXT"
[ -n "$(uname -r | grep "2.6.20")" ] && NEWNOYO="yes"
[ -n "$(uname -r | grep "2.6.21")" ] && NEWNOYO="yes"
[ -n "$(uname -r | grep "2.6.22")" ] && NEWNOYO="yes"
[ -n "$(uname -r | grep "2.6.23")" ] && NEWNOYO="yes"
if [ -n "$NEWNOYO" ]; then
	
	# mkdir -p /boot/grub
	NOYO_PKG="linux-image-2.6.26-bpo.1-686_2.6.26-13~bpo40+1_i386.deb"
	NOYO_URL="http://wawadeb.crdp.ac-caen.fr/iso/$NOYO_PKG"
	NOYO_VERS="linux-image-2.6.26-bpo.1-686"
	MD5_PKG="9277785503e7f2382173a43f11e1fb36"
	cd /root
	wget $NOYO_URL || ERR="Problème lors de la récupération du noyau, vérifiez votre connexion à internet"
	[ "$MD5_PKG" != "$(md5sum $NOYO_PKG | awk '{print $1}')" ] && ERR="Somme Md5 de l'image téléchargée invalide"
	if [ -z "$ERR" ]; then
		dpkg -i $NOYO_PKG
		apt-get install firmware-bnx2 $1
		sed "s/^default.*0/default\t\tsaved/" -i /boot/grub/menu.lst
	else
		echo -e "$COLERREUR"
		echo "$ERR"
		echo -e "$COLTXT"
	fi
else
	mkdir -p /boot/grub
	apt-get install linux-image-2.6.18-6-686
	sed "s/^default.*0/default\t\tsaved/" -i /boot/grub/menu.lst
fi
}

echo -e "$COLPARTIE"
echo "Préparation et tests du système" 
echo -e "$COLTXT"


DEBIAN_VERSION=`cat /etc/debian_version`
[ "$DEBIAN_VERSION" != "3.1" ] && ERREUR "Ce script doit être lancé sous sarge !!!"



cat >/etc/apt/sources.list <<END
deb http://archive.debian.org/debian sarge main non-free contrib
deb ftp://wawadeb.crdp.ac-caen.fr/debian sarge se3
END

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
	ARCHIVE_FILE="migration_sarge2etch.tgz"
	ARCHIVE_FILE_MD5="migration_sarge2etch.md5"
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
	MD5_CTRL_LOCAL1=$(md5sum se3_upgrade_etch.sh)
	MD5_CTRL_LOCAL2=$(md5sum migration_ldap_etch.sh)
	# MD5_CTRL_LOCAL3=$(md5sum migration_UTF8.sh)
	cd -
	MD5_CTRL1=$(cat se3_upgrade_etch.md5)
	MD5_CTRL2=$(cat migration_ldap_etch.md5)
	# MD5_CTRL3=$(cat migration_UTF8.md5)
	chmod +x *.sh

	[ "$MD5_CTRL1" != "$MD5_CTRL_LOCAL1" ] && RELANCE="YES" && cp se3_upgrade_etch.sh $SCRIPTS_DIR/
	[ "$MD5_CTRL2" != "$MD5_CTRL_LOCAL2" ] && cp migration_ldap_etch.sh $SCRIPTS_DIR/
	# [ "$MD5_CTRL3" != "$MD5_CTRL_LOCAL3" ] && cp migration_UTF8.sh $SCRIPTS_DIR/
	if [ "$RELANCE" == "YES" ]
	then
		echo -e "$COLINFO"
		echo "Les scripts de migration ont été mis à jour depuis le serveur central, veuiller relancer se3_upgrade_etch.sh"
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

echo -e "$COLINFO"
echo "Vérification de la version du noyau "
echo -e "$COLTXT"

if [ "$(uname -r)" != "2.6.20digloo2.30" ]; then
echo -e "$COLERREUR"
echo -e "Attention\nVous utilisez un noyau $(uname -r), ce n'est pas le noyau standard de l'installeur version 2.32"
echo -e "Votre noyau sera sans doute désinstallé durant la procedure de migration. Il n'est pas possible de faire autrement pour cause de résolution de dépendances"
echo -e 'A la question du systeme "Remove the running kernel image (not recommended)" Répondez "yes"'
echo -e "$COLTXT"
echo -e "Immediatement apres la desinstallation de votre noyau $(uname -r), un nouveau noyau sera installé"
FORCE_MAJNOYO="yes"
POURSUIVRE

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


echo -e "$COLPARTIE"
echo "Partie 2 : Mise en conformité de l'annuaire pour Etch" 
echo -e "$COLTXT"
POURSUIVRE
if [ "$IP_LOCAL" != "$LDAP_SERVER" ]
then
        echo -e "$COLTXT"
	echo "ATTENTION : Serveur LDAP déporté"
        echo "Le script doit nettoyer le contenu de l'annuaire local avant passage en Etch"
	sleep 1
	echo "Arrêt du serveur d'annuaire..."
	echo -e "$COLCMD\c"
	/etc/init.d/slapd stop
	echo -e "$COLTXT"
	echo "Préparation de la nouvelle arborescence /var/lib/ldap"
	echo -e "$COLCMD\c"
	mv /var/lib/ldap /var/lib/ldap.upgrade_etch
	mkdir /var/lib/ldap
	cp /var/lib/ldap.upgrade_etch/DB_CONFIG /var/lib/ldap/
	echo -e "$COLTXT"
	echo -e "Redémarrage du serveur d'annuaire LDAP"
	echo -e "$COLCMD\c"
	/etc/init.d/slapd start
	
	
else

	LDAP_REPLICA=`echo "SELECT value FROM params WHERE name=\"replica_status\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	
	TEST_SCHEMACHECK=`grep -i "schemacheck.*off" /etc/ldap/slapd.conf`
	if [ "$TEST_SCHEMACHECK" != "" ]
	then
		echo "Migration de l'annuaire en format compatible Etch (schéma check on)"
		/usr/share/se3/sbin/migration_ldap_etch.sh sarge2etch noverif
		if [ "$(wc -c /var/log/se3/migration/import_schemacheck_on.log | cut -f1 -d" ")" != 0 ]; then
		echo -e "$COLERREUR"
		echo "Le script de migration de l'annuaire vers Etch a rapporté des erreurs"
		echo "Il vous appartient de poursuivre ou de couper le script afin de consulter les erreurs dans le journal"
		# 	echo ""
		POURSUIVRE
		fi
	
	# 	exit
	fi
fi
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
echo "Partie 3 : Migration en etch - installations des paquets prioritaires" 
echo -e "$COLTXT"
POURSUIVRE

if [ ! -z "$(dpkg -s se3-ocs-clientlinux 2>/dev/null| grep "Status: install ok")" ]; then 
	apt-get remove se3-ocs-clientlinux -y --purge 
	rm -Rf /etc/ocsinventory-client
	rm -f /etc/cron.d/ocsinventory-client
	rm -rf usr/share/ocsinventory-NG
	rm -f /usr/sbin/ocsinventory-client.pl
	rm -f /bin/ocsinv 

# ajout keyser nettoyage rotation des logs
rm -f /etc/logrotate.d/ocsinventory-client 
/etc/init.d/sysklogd restart
fi

mv /etc/apt/sources.list /etc/apt/sources.list_save_migration
echo "# Sources standard:
deb http://ftp.fr.debian.org/debian/ etch main non-free contrib
deb-src http://ftp.fr.debian.org/debian/ etch main non-free contrib

# Security Updates:
deb http://security.debian.org/ etch/updates main contrib non-free

# sources pour se3
deb ftp://wawadeb.crdp.ac-caen.fr/debian sarge se3

#### Sources XP désactivee en prod ####
#deb ftp://wawadeb.crdp.ac-caen.fr/debian sarge se3XP

# entree pour clamav derniere version
deb http://ftp2.de.debian.org/debian-volatile etch/volatile main" > /etc/apt/sources.list
	
# 	# On se lance
echo "Dpkg::Options {\"--force-confold\";}" > /etc/apt/apt.conf	
# 	echo "Dpkg::Options {\"--force-confnew\";}" > /etc/apt/apt.conf
echo -e "$COLINFO"
echo "mise à jour des dépots...Patientez svp" 
echo -e "$COLTXT"
apt-get update >/dev/null || ERREUR "Une erreur s'est produite lors de la mise à jour des paquets disponibles"
echo "Ok !"
echo -e "$COLINFO"
echo "mise à jour de lib6 et des locales" 
echo -e "$COLTXT"
echo -e "${COLINFO}Ne pas s'alarmer des erreurs sur les locales, c'est inévitable à cette étape de la migration\n Il est également possible que le noyau en cours se désinstalle, un autre sera installé ensuite$COLTXT"
apt-get install libc6 locales -y < /dev/tty

if [ "$?" != "0" ]; then
mv /etc/apt/sources.list_save_migration /etc/apt/sources.list 
ERREUR "Une erreur s'est produite lors de la mise à jour des paquets lib6 et locales"
fi

if [ "$FORCE_MAJNOYO" == "yes" ]
then 
echo -e "$COLINFO"
echo "Installation du nouveau noyau"
echo -e "$COLTXT"
INSTALLNOYO 
fi

echo "Arrêt si necessaire du service Backuppc"
/etc/init.d/backuppc stop >/dev/null

echo -e "$COLPARTIE"
echo "Partie 4 : Migration en etch - installations des paquets restants" 
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

# Lien pour logonpl
[ ! -e /usr/lib/libmysqlclient.so.12 ] && ln -s /usr/lib/libmysqlclient.so.15 /usr/lib/libmysqlclient.so.12

echo -e "$COLINFO"
echo "Fin de la migration du système"
echo -e "$COLTXT"

echo -e "$COLINFO"
echo "Nettoyage de /home/netlogon......Patientez !"
echo -e "$COLTXT"
rm /home/netlogon/*.bat
rm /home/netlogon/*.txt

perl -pi -e "s/sarge/etch/;" /etc/apt/sources.list
echo -e "$COLPARTIE"
echo "Partie 5 : Mise à jour du paquet se3 sous sa version Etch" 
echo -e "$COLTXT"

apt-get update >/dev/null
apt-get install se3 $option --allow-unauthenticated
echo -e "$COLINFO"
echo "Nettoyage d'anciens paquets inutiles"
echo -e "$COLTXT"
	
# On désinstalle php4 et cie
apt-get remove --purge php4 php4-ldap php4-mysql php4-gd php4-cli php4-common libapache-mod-php4 libapache2-mod-php4
# 	dpkg -P libmysqlclient12 libmysqlclient14  mysql-common-4.1 mysql-server-4.1
# 	dpkg -P mysql-server
[ -z "$(dpkg -s se3-ocs 2>/dev/null | grep "Status: install ok")" ] && apt-get install se3-ocs -y --allow-unauthenticated
[ -z "$(dpkg -s se3-ocs-clientwin  2>/dev/null | grep "Status: install ok")" ] && apt-get install se3-ocs-clientwin -y --allow-unauthenticated
mkdir -p /var/lib/ocsinventory-client
apt-get install ocsinventory-agent -y --allow-unauthenticated
echo "server=localhost:909" > /etc/ocsinventory/ocsinventory-agent.cfg
# On vire les modules php4 qui sont pas purgés
# Voir s'il ne faut pas stopper apache avant de les purger
rm /etc/apache2se/mods-enabled/php4.*
rm -Rf /etc/php4

# Modification du fichier php.ini

# Modif des paramètres apache pour les scripts de création des comptes
perl -pi -e 's&;include_path = ".:/usr/share/php"&include_path=".:/var/www/se3/includes"&' /etc/php5/apache2/php.ini
sed "s/#AddDefaultCharset.*/AddDefaultCharset ISO-8859-1/" -i /etc/apache2se/apache2.conf
perl -pi -e "s/OCS_MODPERL_VERSION 1/OCS_MODPERL_VERSION 2/" /etc/apache2se/conf.d/ocsinventory.conf

echo "Redémarrage des services...."
echo -e "$COLCMD"
# A désactiver ! utf8 not rulaize !
# perl -pi -e 's&#AddDefaultCharset.*&AddDefaultCharset	UTF8&' /etc/apache2se/apache2.conf
/etc/init.d/apache2se restart
/etc/init.d/mysql restart
/etc/init.d/samba restart

GENLOCALES

GENDISCOVER


if [ "$FORCE_MAJNOYO" != "yes" ]
then 
echo -e "$COLINFO"
echo "Installation du nouveau noyau"
echo -e "$COLTXT"
INSTALLNOYO --allow-unauthenticated
fi

rm -f /tmp/*.sh
echo -e "$COLINFO"
echo "Terminé !!!"
echo -e "$COLINFO"
echo "ATTENTION ! "
echo "Le système a été migré en utf8 mais les fichiers des utilisateurs dont les noms contiennent des accents stockés"
echo "sur /home et /var/se3 demeurent en iso. En l'état Samba continuera à fonctionner en iso"
echo "Si vous souhaitez basculer en utf8 Samba et les fichiers des utilisateurs (ce qui peut etre utile si vous avez" 
echo "des clients linux qui fonctionnent en utf8 par défaut, vous devrez lancer le script prévu à cette effet :"
echo "/usr/share/se3/sbin/migration_UTF8.sh" 
echo -e "$COLTXT"

[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_REPORT

rm -f /etc/apt/apt.conf
DEBIAN_PRIORITY="high"
DEBIAN_FRONTEND="dialog" 
export  DEBIAN_PRIORITY
export  DEBIAN_FRONTEND
exit 0
