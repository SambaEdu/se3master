#!/bin/sh

## $Id: se3_upgrade_lenny.sh 6505 2011-10-06 12:34:00Z keyser $ ##

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
deb http://ftp2.de.debian.org/debian-volatile squeeze/volatile main

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
chemin_log="/root/migration_lenny2squeeze"
mkdir -p $chemin_log
fichier_log="$chemin_log/migration-$LADATE.log"
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
echo "* Script de migration de Etch vers Squeeze    *" | tee -a $fichier_log
echo "*********************************************"
# echo "        /!\ ----- ATTENTION ---- /!\ 
# Le script videra la base de clefs de registre durant la migration
# Si vous avez des clefs personnelles veuillez les sauvegarder avant !!
# "
echo -e "$COLTXT"
POURSUIVRE

sleep 1

[ -e /root/debug ] && DEBUG="yes"
[ -e /root/nodl ] && NODL="yes"
NODL="yes"
  
echo -e "$COLPARTIE"
echo "Preparation et tests du systeme" | tee -a $fichier_log
echo -e "$COLTXT"

# 
# DEBIAN_VERSION=`cat /etc/debian_version`
# [ "$DEBIAN_VERSION" != "4.0" ] && ERREUR "Ce script doit être lance sous etch !!!"

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
	MD5_CTRL_LOCAL1=$(md5sum se3_upgrade_lenny.sh)
	# MD5_CTRL_LOCAL3=$(md5sum migration_UTF8.sh)
	cd -
	MD5_CTRL1=$(cat se3_upgrade_lenny.md5)
	#MD5_CTRL2=$(cat migration_ldap_lenny.md5)
	# MD5_CTRL3=$(cat migration_UTF8.md5)
	chmod +x *.sh

	[ "$MD5_CTRL1" != "$MD5_CTRL_LOCAL1" ] && RELANCE="YES" && cp se3_upgrade_lenny.sh $SCRIPTS_DIR/
# 	[ "$MD5_CTRL2" != "$MD5_CTRL_LOCAL2" ] && cp migration_ldap_etch.sh $SCRIPTS_DIR/
	# [ "$MD5_CTRL3" != "$MD5_CTRL_LOCAL3" ] && cp migration_UTF8.sh $SCRIPTS_DIR/
	if [ "$RELANCE" == "YES" ]
	then
		echo -e "$COLINFO"
		echo "Les scripts de migration ont ete mis a jour depuis le serveur central, veuiller relancer se3_upgrade_lenny.sh"
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

PARTROOT=`df | grep "/\$" | sed -e "s/ .*//"`
PARTROOT_SIZE=$(fdisk -s $PARTROOT)
rm -f /root/dead.letter
if [ "$PARTROOT_SIZE" -le 1800000 ]; then
	ERREUR "La partition racine fait moins de 1.8Go, c'est insuffisant pour passer en Squeeze" | tee -a $fichier_log
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

# 
# echo -e "$COLPARTIE"
# echo "Partie 2a : Suppression paquet cupsys (sera remplace par cups une fois en lenny) " 
# echo -e "$COLTXT"
# 
# POURSUIVRE
# apt-get remove cupsys cupsys-client || erreur="yes"
# 
# 	
# echo -e "$COLPARTIE"
# echo "Partie 2b : Suppression paquet backuppc et se3-ocs" 
# echo -e "$COLTXT"
# echo "les paquet backuppc et ocs doivent etre supprimes avant la migration. "
# echo "Il sera possible d'installer les modules correspondants une fois la migration effectuee."
# 
# POURSUIVRE
# rm -f /etc/backuppc/config.pl.divert
# rm -f /etc/backuppc/localhost.pl.divert
# rm -f /etc/backuppc/apache.conf.divert
# rm -f /usr/share/backuppc/lib/BackupPC/CGI/Lib.pm.divert
# rm -f /usr/share/backuppc/image/BackupPC_stnd.css
# rm -f /usr/share/backuppc/image/BackupPC_stnd.css.ori
# 
# 
# 
# if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /etc/backuppc/config.pl)" ]; then
# dpkg-divert --package se3 --remove --rename /etc/backuppc/config.pl 
# fi 
# 
# if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /etc/backuppc/localhost.pl)" ]; then
# dpkg-divert --package se3 --remove --rename /etc/backuppc/localhost.pl 
# fi 
# 
# if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /etc/backuppc/apache.conf)" ]; then
# dpkg-divert --package se3 --remove --rename /etc/backuppc/apache.conf
# fi 
# 
# if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /usr/share/backuppc/image/BackupPC_stnd.css)" ]; then
# dpkg-divert --package se3 --remove --rename /usr/share/backuppc/image/BackupPC_stnd.css 
# fi 
# 
# if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /usr/share/backuppc/lib/BackupPC/CGI/Lib.pm)" ]; then
#  
# dpkg-divert --package se3 --remove --rename /usr/share/backuppc/lib/BackupPC/CGI/Lib.pm
# fi 
# 
# 
# # apt-get remove backuppc --purge
# # supression entree backuppc base sql
# echo "DELETE FROM params WHERE name='backuppc'"| mysql -h $dbhost $dbname -u $dbuser -p$dbpass


df -h | grep backuppc && umount /var/lib/backuppc
if [ ! -z "$(df -h | grep /var/lib/backuppc/)" ]; then 
    ERREUR "Il semble qu'une ressource soit montee sur /var/lib/backuppc. Il faut la demonter puis relancer"
    exit 1
fi
# 
# apt-get remove backuppc se3-ocs ocsinventory-agent --purge || erreur="yes"
# rm -f /etc/apache2se/conf.d/ocsinventory.conf 
# echo "DELETE FROM params WHERE name='inventaire'"| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
# #. /usr/share/se3/includes/config.inc.sh -o

# purges trace slapd backup 
rm -rf /var/backups/slapd*
rm -rf /var/backups/${ldap_base_dn}*


# echo "" > /etc/environment 
if [ "$erreur" == "yes" ]; then 
  echo -e "$COLERREUR Une erreur s'est produite lors de la supression des paquets"
  echo "il serait plus prudent de couper le script et de resoudre le probleme avant de poursuivre"
  POURSUIVRE
else
  touch $chemin_log/phase2-ok
fi

echo -e "$COLPARTIE"
echo "Partie 3 : Migration en Squeeze - installations des paquets prioritaires" 
echo -e "$COLTXT"
POURSUIVRE

[ -z "$LC_ALL" ] && LC_ALL=C && export LC_ALL=C 
[ -z "$LANGUAGE" ] && export LANGUAGE=fr_FR:fr:en_GB:en  
[ -z "$LANG" ] && export LANG=fr_FR@euro 



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
fi
touch $chemin_log/phase3-ok
echo "mise a jour de lib6 et des locales OK" | tee -a $fichier_log

echo -e "$COLPARTIE"
echo "Partie 4 : Migration en Sqeeze - installations des paquets restants" 
echo -e "$COLTXT"
POURSUIVRE
echo -e "$COLINFO"
echo "migration du systeme lancee.....ça risque d'être long ;)" 
echo -e "$COLTXT"

apt-get dist-upgrade $option  < /dev/tty


if [ "$?" != "0" ]; then
	echo -e "$COLERREUR Une erreur s'est produite lors de la migration vers lenny"
	 GENSOURCELIST
	echo "Vous devrez terminer la migration manuellement une fois votre probleme regle"
	echo -e "$COLTXT"
	ERREUR "la migration ne peut se poursuivre"
	#/usr/share/se3/scripts/install_se3-module.sh se3
fi

touch $chemin_log/phase4-ok
echo "migration du systeme OK" | tee -a $fichier_log

#Install ssmtp si necessaire
apt-get install ssmtp -y >/dev/null 


echo -e "$COLPARTIE"
echo "Partie 5 : Nettoyage de la BDD et des fichiers obsoletes" 
echo -e "$COLTXT"

if [ -e /home/netlogon/EnableGPO.bat ]; then
    mv  /home/netlogon/EnableGPO.bat /root/
    rm -f /home/netlogon/*.bat
    rm -f /home/netlogon/*.txt
    mv  /root/EnableGPO.bat /home/netlogon/
else
    rm -f /home/netlogon/*.bat
    rm -f /home/netlogon/*.txt
fi

echo -e "$COLINFO"
echo "Déplacement si existant des homes orphelins dans le home d'admin (Trash_users)"
echo -e "$COLTXT"
mkdir -p /home/admin/Trash_users
find /home -maxdepth 1 -nouser -exec mv -v {} /home/admin/Trash_users/ \; 
chown -R admin /home/admin/Trash_users


echo -e "$COLINFO"
echo "Suppression des anciens profils XP obsoletes des repertoires personnels"
echo "ceci peut etre assez long la commande se lancera ce soir a 20h00"
sleep 2
echo -e "$COLTXT"

at_script="$chemin_log/clean_old_profiles.sh"
cat > $at_script <<END
#!/bin/bash
ls /home/ | while read A
do
     if [ -e "/home/\$A/profile" ]; then
	echo "Suppression de l'ancien profil XP de \$A" 
	rm -fr /home/\$A/profile
     fi
done
END
chmod 700 $at_script
at 20:00 -f $at_script
sleep 2

echo -e "$COLINFO"
echo "Nettoyage de se3db - cles, groupes de cles et restrictions"
echo -e "$COLTXT"
echo "TRUNCATE TABLE restrictions" | mysql $dbname -u $dbuser -p$dbpass 

echo "La table restriction est desormais vide. 
Les cles et les groupes de cles peuvent egalement être vides sans risque si vous n'avez pas de cles personnelles.
Cela a l'avantage de ne pas garder des cles opsoletes ou incompatibles lors de votre migration ! 

Il vous suffira de mettre a jour la base de cles une fois sous lenny pour importer celles par defaut" 

REPONSE=""
while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
do
    echo -e "$COLTXT"
    echo -e "On remet a zero les tables ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
    read REPONSE
    if [ -z "$REPONSE" ]; then
	    REPONSE="o"
    fi
done

if [ "$REPONSE" == "o" -o "$REPONSE" == "O" ]; then
    echo "on vide !"
    echo "TRUNCATE TABLE corresp" | mysql $dbname -u $dbuser -p$dbpass
    echo "TRUNCATE TABLE modele"| mysql $dbname -u $dbuser -p$dbpass
    
else
    echo "On laisse les choses en place !"
fi

echo -e "$COLPARTIE"
echo "Partie 6 : Mise a jour des paquets se3 sous lenny"  | tee -a $fichier_log
echo -e "$COLTXT"

GENSOURCESE3

/usr/share/se3/scripts/install_se3-module.sh se3 | tee -a $fichier_log

# Recuperation des params LDAP
#
# Mise a jour annuaire (suite changement dans samba)
#/usr/share/se3/sbin/migrationLenny.sh

# 
# echo -e "$COLINFO"
# echo "Modification de l'attribut sambaPwdLastSet de tous les utilisateurs"
# echo -e "$COLTXT"
# # Modification de l'attribut sambaPwdLastSet
# ldapsearch -xLLL -D $adminRdn,$ldap_base_dn -w $adminPw objectClass=person uid| grep uid:| cut -d ' ' -f2| while read uid
# do
# 		(
# 		echo "dn: uid=$uid,$peopleRdn,$ldap_base_dn"
# 		echo "changetype: modify"
# 		echo "replace: sambaPwdLastSet"
# 		echo "sambaPwdLastSet: 1"
# 		) | ldapmodify -x -D $adminRdn,$ldap_base_dn -w $adminPw >/dev/null 2>&1
# 		if [ "$?" != "0" ]
# 		then
# 			#corbeille
# 			  (
# 	                echo "dn: uid=$uid,ou=Trash,$ldap_base_dn"
# 	                echo "changetype: modify"
# 	                echo "replace: sambaPwdLastSet"
# 	                echo "sambaPwdLastSet: 1"
# 	                ) | ldapmodify -x -D $adminRdn,$ldap_base_dn -w $adminPw >/dev/null
# 
# 		fi
# done

# Modification du fichier php.ini

# Modif des parametres apache pour les scripts de creation des comptes
# perl -pi -e 's&;include_path = ".:/usr/share/php"&include_path=".:/var/www/se3/includes"&' /etc/php5/apache2/php.ini
# sed "s/#AddDefaultCharset.*/AddDefaultCharset ISO-8859-1/" -i /etc/apache2se/apache2.conf
# perl -pi -e "s/OCS_MODPERL_VERSION 1/OCS_MODPERL_VERSION 2/" /etc/apache2se/conf.d/ocsinventory.conf
echo -e "$COLINFO"
echo "Redemarrage des services...."
echo -e "$COLCMD"
# A desactiver ! utf8 not rulaize !
# perl -pi -e 's&#AddDefaultCharset.*&AddDefaultCharset	UTF8&' /etc/apache2se/apache2.conf
/etc/init.d/apache2se restart
/etc/init.d/mysql restart
/etc/init.d/samba restart

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
