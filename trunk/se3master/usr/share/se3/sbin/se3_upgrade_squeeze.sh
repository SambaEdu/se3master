#!/bin/sh

## $Id$ ##

#####Script permettant de migrer un serveur Se3 de Etch en Squeeze#####

#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLCMD="\033[1;37m"     # Blanc
COLERREUR="\033[1;31m"  # Rouge
COLTXT="\033[0;37m"     # Gris
COLINFO="\033[0;36m"	# Cyan
COLPARTIE="\033[1;34m"	# Bleu

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




LINE_TEST()
{
if ( ! wget -q --output-document=/dev/null 'ftp://wawadeb.crdp.ac-caen.fr/welcome.msg') ; then
	ERREUR "Votre connexion internet ne semble pas fonctionnelle !!" 
	exit 1
fi
}

GENSOURCELIST()
{
mv /etc/apt/sources.list /etc/apt/sources.list_save_migration
cat >/etc/apt/sources.list <<END
# Sources standard:
deb http://ftp.fr.debian.org/debian/ squeeze main non-free contrib
deb-src http://ftp.fr.debian.org/debian/ squeeze main non-free contrib

# Security Updates:
deb http://security.debian.org/ squeeze/updates main contrib non-free

# entree pour clamav derniere version
# deb http://ftp2.de.debian.org/debian-volatile squeeze/volatile main

# Pour wine
deb http://www.lamaresh.net/apt squeeze main

END
}

GENSOURCESE3()
{

cat >/etc/apt/sources.list.d/se3.list <<END
# sources pour se3
deb ftp://wawadeb.crdp.ac-caen.fr/debian squeeze se3

#### Sources testing desactivee en prod ####
#deb ftp://wawadeb.crdp.ac-caen.fr/debian squeeze se3testing

#### Sources XP desactivee en prod ####
#deb ftp://wawadeb.crdp.ac-caen.fr/debian squeeze se3XP
END
}
#date
LADATE=$(date +%d-%m-%Y)
chemin_migr="/root/migration_lenny2squeeze"
mkdir -p $chemin_migr
fichier_log="$chemin_migr/migration-$LADATE.log"
touch $fichier_log


MAIL_REPORT()
{

[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $fichier_log)
	#On envoie un mail a  l'admin
	echo "$REPORT"  | mail -s "[SE3] Rapport de migration $0" $MAIL_ADMIN
fi
}


option="-y"
PERMSE3_OPTION="--light"
DEBIAN_PRIORITY="critical"
DEBIAN_FRONTEND="noninteractive"
export  DEBIAN_FRONTEND
export  DEBIAN_PRIORITY



echo -e "$COLTITRE"
echo "*********************************************"
echo "* Script de migration de Lenny vers Squeeze *" | tee -a $fichier_log
echo "*********************************************"
# echo "        /!\ ----- ATTENTION ---- /!\ 
# "
echo -e "$COLTXT"
POURSUIVRE

sleep 1

[ -e /root/debug ] && DEBUG="yes"
[ -e /root/nodl ] && NODL="yes"


### mode debug et nodl pour le moment ###
NODL="yes"
DEBUG="yes"
#########################################  


echo -e "$COLPARTIE"
echo "Preparation et tests du systeme" | tee -a $fichier_log
echo -e "$COLTXT"

# On teste la version de debian
 
if  ! egrep -q "^5.0" /etc/debian_version;  then
        if egrep -q "^6.0" /etc/debian_version; then
                echo "Votre serveur est deja en version Debian Squeeze"
                exit 0
        else
                echo "Votre serveur n'est pas en version Debian Lenny."
                echo "Operation annulee !"
                exit 1
        fi
fi

LINE_TEST

if [ "$NODL" != "yes" ]; then
	echo -e "$COLINFO"
	echo "Verification en ligne que vous avez bien la derniere version des scripts de migration"
	echo -e "$COLTXT"
	cd /root
	ARCHIVE_FILE="migration_lenny2squeeze.tgz"
	ARCHIVE_FILE_MD5="migration_lenny2squeeze.md5"
	SCRIPTS_DIR="/usr/share/se3/sbin"
	
	rm -f $ARCHIVE_FILE_MD5 $ARCHIVE_FILE
	wget -N -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr/majse3/$ARCHIVE_FILE
	wget -N -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr/majse3/$ARCHIVE_FILE_MD5
	MD5_CTRL=$(cat $ARCHIVE_FILE_MD5)
	MD5_CTRL_LOCAL=$(md5sum $ARCHIVE_FILE)
	if [ "$MD5_CTRL" != "$MD5_CTRL_LOCAL" ]
	then	
		echo -e "$COLERREUR"
		echo "Controle MD5 de l'archive incorrecte, relancez le script afin qu'elle soit de nouveau telechargee"
		echo -e "$COLTXT"
		exit 1
	fi

	tar -xzf $ARCHIVE_FILE
	cd $SCRIPTS_DIR
	MD5_CTRL_LOCAL1=$(md5sum se3_upgrade_squeeze.sh)
	# MD5_CTRL_LOCAL3=$(md5sum migration_UTF8.sh)
	cd -
	MD5_CTRL1=$(cat se3_upgrade_squeeze.md5)
	#MD5_CTRL2=$(cat migration_ldap_lenny.md5)
	# MD5_CTRL3=$(cat migration_UTF8.md5)
	chmod +x *.sh

	if [ "$MD5_CTRL1" != "$MD5_CTRL_LOCAL1" ]; then
		RELANCE="YES" 
		cp se3_upgrade_squeeze.sh $SCRIPTS_DIR/
	fi
	if [ "$RELANCE" == "YES" ]
	then
		echo -e "$COLINFO"
		echo "Les scripts de migration ont ete mis a jour depuis le serveur central, veuiller relancer se3_upgrade_squeeze.sh"
		echo "afin de prendre en compte les changements"
		exit 1
		echo -e "$COLTXT"
	
	
	fi
	echo -e "$COLINFO"
	echo "Vous disposez de la derniere version des scritps de migration, la migration peut se poursuivre..."
	sleep 2
	echo -e "$COLTXT"
else
echo "mode debug pas de telechargement"
sleep 2
fi



#init des params
. /usr/share/se3/includes/config.inc.sh -cml

# On teste si on a de la place pour faire la maj
PARTROOT=`df | grep "/\$" | sed -e "s/ .*//"`
PARTROOT_SIZE=$(fdisk -s $PARTROOT)
rm -f /root/dead.letter
if [ "$PARTROOT_SIZE" -le 1800000 ]; then
	ERREUR "La partition racine fait moins de 1.8Go, c'est insuffisant pour passer en Squeeze" | tee -a $fichier_log
	exit 1
fi

if [ "$replica_status" == "" -o "$replica_status" == "0" ]
then
	echo "Serveur ldap en standalone ---> OK"
else
	ERREUR "Le serveur ldap soit etre en standalone (pas de replication ldap) !!!\nModifiez cette valeur et relancez le script" | tee -a $fichier_log
	exit 1
fi

[ "$DEBUG" != "yes" ] && apt-get clean
USE_SPACE=$(df -h | grep "/var$" | awk '{print $5}' | sed -e s/%//)
if [ ! $USE_SPACE -le 80 ]; then 
    ERREUR "Pas assez de place sur le disque (partition /var > 80% ) pour lancer la mise a jour" | tee -a $fichier_log
    exit 1
fi

if [ ! -e $chemin_log/phase1-ok ]; then


    echo -e "$COLINFO"
    echo "mise a jour des paquets disponibles....Patientez svp"
    echo -e "$COLTXT"
    apt-get -qq update
    echo "installation si besoin de debian-archive-keyring"
    apt-get install debian-archive-keyring --allow-unauthenticated
    SE3_CANDIDAT=$(apt-cache policy se3 | grep "Candidat" | awk '{print $2}')
    SE3_INSTALL=$(apt-cache policy se3 | grep "Install" | awk '{print $2}')
    [ "$SE3_CANDIDAT" != "$SE3_INSTALL" ] && ERREUR "Il semble que votre serveur se3 n'est pas a jour\nMettez votre serveur a jour puis relancez le script de migration"

    echo -e "$COLPARTIE"
    echo "Migration phase 1 : Mise a jour modules SE3 si necessaire"
    echo -e "$COLTXT"
    /usr/share/se3/scripts/install_se3-module.sh se3 | grep -v "pre>"
    #/usr/share/se3/scripts/se3-upgrade.sh | grep -v pre


    if [ "$?" != "0" ]; then
    ERREUR "Une erreur s'est produite lors de la mise à jour des modules"
    fi
    touch $chemin_log/phase1-ok
fi



df -h | grep backuppc && umount /var/lib/backuppc
if [ ! -z "$(df -h | grep /var/lib/backuppc)" ]; then 
    ERREUR "Il semble qu'une ressource soit montee sur /var/lib/backuppc. Il faut la demonter puis relancer"
    exit 1
fi

echo -e "$COLPARTIE"
echo "Partie 2 : Installation prealable du paquet insserv" 
echo -e "$COLTXT"
## Install de insert avant de basculer en squeeze
apt-get install insserv -y


if [ "$erreur" == "yes" ]; then 
  echo -e "$COLERREUR Une erreur s'est produite lors de l'installation"
#   echo "il serait plus prudent de couper le script et de resoudre le probleme avant de poursuivre"
   POURSUIVRE
else
  touch $chemin_log/phase2-ok
fi

## LDAP
# purges trace slapd backup 
rm -rf /var/backups/slapd*
rm -rf /var/backups/${ldap_base_dn}*

cat /var/lib/ldap/DB_CONFIG | grep -v "sactivation logs ldap" > $chemin_migr/DB_CONFIG
cp $chemin_migr/DB_CONFIG /var/lib/ldap/DB_CONFIG
cp /etc/ldap/slapd.conf $chemin_migr/

chown -R openldap:openldap /var/lib/ldap/

# echo "" > /etc/environment 


echo -e "$COLPARTIE"
echo "Partie 3 : Migration en Squeeze - installations des paquets prioritaires" 
echo -e "$COLTXT"
POURSUIVRE

[ -z "$LC_ALL" ] && LC_ALL=C && export LC_ALL=C 
[ -z "$LANGUAGE" ] && export LANGUAGE=fr_FR:fr:en_GB:en  
[ -z "$LANG" ] && export LANG=fr_FR@euro 


# Creation du source.list squeeze

GENSOURCELIST

# On se lance
echo "Dpkg::Options {\"--force-confold\";}" > /etc/apt/apt.conf	
# 	echo "Dpkg::Options {\"--force-confnew\";}" > /etc/apt/apt.conf
echo -e "$COLINFO"
echo "mise a jour des depots...Patientez svp" 
echo -e "$COLTXT"
apt-get -qq update || ERREUR "Une erreur s'est produite lors de la mise a jour des paquets disponibles. Reglez le probleme et relancez le script"
echo "Ok !"
echo -e "$COLINFO"
echo "Maj si besoin de debian-archive-keyring"

apt-get install debian-archive-keyring --allow-unauthenticated
apt-get -qq update 
echo "mise a jour de lib6 et des locales" | tee -a $fichier_log
echo -e "$COLTXT"
echo -e "${COLINFO}Ne pas s'alarmer des erreurs sur les locales, c'est inevitable a cette etape de la migration\n Il est egalement possible que le noyau en cours se desinstalle, un autre sera installe ensuite$COLTXT"

apt-get install libc6 locales wine -y < /dev/tty 

if [ "$?" != "0" ]; then
	mv /etc/apt/sources.list_save_migration /etc/apt/sources.list 
	ERREUR "Une erreur s'est produite lors de la mise a jour des paquets lib6 et locales. Reglez le probleme et relancez le script"
	exit 1
fi
touch $chemin_log/phase3-ok
echo "mise a jour de lib6 locales et wine ---> OK" | tee -a $fichier_log

echo -e "$COLPARTIE"
echo "Partie 4 : Migration en Squeeze - installations des paquets restants" 
echo -e "$COLTXT"
POURSUIVRE
echo -e "$COLINFO"
echo "migration du systeme lancee.....ça risque d'être long ;)" 
echo -e "$COLTXT"

apt-get dist-upgrade $option  < /dev/tty


if [ "$?" != "0" ]; then
	echo -e "$COLERREUR Une erreur s'est produite lors de la migration vers squeeze"
	 GENSOURCELIST
	echo "Vous devrez terminer la migration manuellement une fois votre probleme regle"
	echo -e "$COLTXT"
	ERREUR "la migration ne peut se poursuivre"
	exit 1
	#/usr/share/se3/scripts/install_se3-module.sh se3
fi

touch $chemin_log/phase4-ok
echo "migration du systeme OK" | tee -a $fichier_log

#Install ssmtp si necessaire
apt-get install ssmtp -y >/dev/null 

# Retour Slapd.conf
/etc/init.d/slapd stop
sed -i "s/#SLAPD_CONF=/SLAPD_CONF=\"\/etc\/ldap\/slapd.conf\"/g" /etc/default/slapd
cp $chemin_migr/slapd.conf /etc/ldap/slapd.conf
chown openldap:openldap /etc/ldap/slapd.conf
sleep 2
/ect/init.d/slapd start

echo -e "$COLPARTIE"
echo "Partie 6 : Mise a jour des paquets se3 sous squeeze"  | tee -a $fichier_log
echo -e "$COLTXT"

GENSOURCESE3

/usr/share/se3/scripts/install_se3-module.sh se3 | tee -a $fichier_log


echo -e "$COLINFO"
echo "Redemarrage des services...."
echo -e "$COLCMD"
/etc/init.d/apache2se restart
/etc/init.d/mysql restart
/etc/init.d/samba restart

# modif base sql
mysql -e "UPDATE se3db.params SET value = 'squeeze' WHERE value = 'lenny';" 


echo -e "$COLINFO"
echo "Termine !!!"
echo -e "$COLTXT"

[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_REPORT

rm -f /etc/apt/apt.conf
DEBIAN_PRIORITY="high"
DEBIAN_FRONTEND="dialog" 
export  DEBIAN_PRIORITY
export  DEBIAN_FRONTEND
exit 0
