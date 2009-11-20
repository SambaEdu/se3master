#!/bin/sh
#
## $Id$ ##
#
##### Permet d'installer un paquet module se3#####
### franck.molle@ac-rouen.fr
SE3MODULE="$1"
M2="$2"
M3="$3"
set -x
if [ "$1" = "--help" -o "$1" = "" -o "$1" = "-h" ]
then
	echo "Script permettant l'installion ou l'activation de $SE3MODULE"
	echo "Usage : passer le nom du module a installer en option"
	exit 1
fi

LADATE=$(date +%d-%m-%Y)
echo "Nous sommes le $LADATE"
REPORT_FILE="/var/log/se3/${SE3MODULE}_install_$LADATE.log" 2>&1
echo "" > $REPORT_FILE 2>&1
### mode auto : on rend debconf silencieux  ###
DEBIAN_PRIORITY="critical"
DEBIAN_FRONTEND="noninteractive"
export  DEBIAN_FRONTEND
export  DEBIAN_PRIORITY

opt="--allow-unauthenticated"


. /etc/profile 2>/dev/null
MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
cat $REPORT_FILE
	#On envoie un mail à l'admin
	echo "$REPORT"  | mail -s "[SE3] Résultat de $0" $MAIL_ADMIN
fi
}


WWWPATH="/var/www"
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo -e "$COLERREUR"
	echo "Fichier de configuration $WWWPATH/se3/includes/config.inc.php inaccessible." | tee -a $REPORT_FILE
	echo "Le script ne peut se poursuivre." | tee -a $REPORT_FILE
	MAIL_REPORT
	echo "</pre>"
	exit 1
fi

# debug="0" #desactivation debug si =0
[ -e /root/debug ] && debug="1"

TEST_LOCK()
# principe bien rempompé sur un script de stéphane ;)
{
# Chemin des fichiers de lock:
chemin_lock="/var/lock"
# Nom du fichier de lock:
fich_lock="$chemin_lock/${SE3MODULE}.lck"
if [ -e $fich_lock ]; then
	t1=$(cat $fich_lock)
	t_expiration=$(($t1+1200))
	t2=$(date +%s)
	difference=$(($t2-$t1))
	if [ $t2 -gt $t_expiration ]; then
		echo "Tâche d'installation de $SE3MODULE initiée en $t1 et il est $t2" | tee -a $REPORT_FILE
		echo "La tâche a dépassé le délai imparti." | tee -a $REPORT_FILE
		echo "Le fichier va être réinitialisé..." | tee -a $REPORT_FILE
	else
		echo "Une installation semble déjà en cours, veuillez patienter qu'elle se termine, celle-ci dispose de 20mn pour le faire" | tee -a $REPORT_FILE
		echo "</pre>"
		exit 1
	fi

else
	date +%s > $fich_lock
fi
}

LINE_TEST()
{
if ( ! wget -q --output-document=/dev/null 'ftp://wawadeb.crdp.ac-caen.fr/welcome.msg') ; then
	echo "Votre connexion internet ne semble pas fonctionnelle !!" | tee -a $REPORT_FILE
	MAIL_REPORT
	echo "</pre>"
	exit 1
fi
}
install_module()
{
echo "Installation / Activation $SE3MODULE" | tee -a $REPORT_FILE
echo "Mise à jour de la liste des paquets disponibles ....." | tee -a $REPORT_FILE
LINE_TEST
TEST_LOCK
apt-get update -qq && (echo "Liste mise à jour avec succès" | tee -a $REPORT_FILE)
echo "" | tee -a $REPORT_FILE

echo "Installation du paquet $SE3MODULE et de ses dépendances" | tee -a $REPORT_FILE
apt-get install $SE3MODULE -y --force-yes $opt | tee -a $REPORT_FILE
if [ ! -z "$M2" ]; then
echo "Installation du paquet complémentaire $M2" | tee -a $REPORT_FILE
apt-get install $M2 -y --force-yes $opt | tee -a $REPORT_FILE
fi

if [ ! -z "$M3" ]; then
echo "Installation du paquet complémentaire $M3" | tee -a $REPORT_FILE
apt-get install $M3 -y --force-yes $opt | tee -a $REPORT_FILE
fi

echo "Installation terminée, suppression du fichier verrou" | tee -a $REPORT_FILE
rm -f $fich_lock
# L'envoi d'un mail est superflu
#MAIL_REPORT
}
echo "<pre>"
## on installe quoi comme module ?
case "$1" in
se3)
install_module
;;

se3-dhcp)
		## test de l'existence d'un dhcp qui fonctionne et actif au boot et svg des fichiers si existants
		# DHCP_ACTIVE=$(ps aux | grep dhcpd | grep -v grep)
		# DHCP_ON_BOOT=$(ls /etc/rc2.d/ | grep dhcp)
		# if [ -e /etc/dhcp3/dhcpd.conf ]; then
		# cp -a /etc/dhcp3/dhcpd.conf /root/
		# else
		# [ -e /etc/dhcpd.conf ] && cp -a /etc/dhcpd.conf /root/
		# fi

		## descente de se3-dhcp
		install_module
		# Activation dans l'interfesse
		mysql -h $dbhost -u $dbuser -p$dbpass -D $dbname -e "UPDATE params SET value='1' WHERE name='dhcp';"

		# restauration de l'état précédent du dhcp si necessaire
		# [ -e /root/dhcpd.conf ] && mv /root/dhcpd.conf /etc/dhcp3/dhcpd.conf
		# [ ! -z $DHCP_ON_BOOT ] && /usr/sbin/update-rc.d dhcp3-server default


;;

se3-clonage)
LINE_TEST
# if [ ! -e /usr/sbin/atftpd ];	then

	cp -a /etc/inetd.conf /etc/inetd.conf.${SE3MODULE}_$LADATE
# 	echo "Installation de atftpd" | tee -a $REPORT_FILE
# 	apt-get install $option atftpd | tee -a $REPORT_FILE
# 	echo "" | tee -a $REPORT_FILE


# fi

if [ -d /tftpboot ]; then
	if [ -z "$(dpkg -s se3-dhcp | grep "Status: install ok")" ]; then
		echo -e "Présence de /tftpboot détectée, se3-clonage a renommé le répertoire en /tftpboot_${SE3MODULE}.sav" | tee -a $REPORT_FILE
		mv /tftpboot /tftpboot_${SE3MODULE}.sav
	fi

fi
install_module
cp -a /etc/inetd.conf.${SE3MODULE}_$LADATE /etc/inetd.conf
## Activation du tftp
/usr/share/se3/scripts/se3_tftp_boot_pxe.sh start

# Activation dans l'interfesse
mysql -h $dbhost -u $dbuser -p$dbpass -D $dbname -e "UPDATE params SET value='1' WHERE name='clonage';"
;;

se3-clamav)
install_module

	echo "Récupération du paquet se3-clamav si necessaire (activation possible via l'interface)"
	mv /etc/clamav/freshclam.conf /etc/clamav/freshclam.conf_se3sauv_$LADATE
echo "DatabaseOwner clamav
UpdateLogFile /var/log/clamav/freshclam.log
LogFileMaxSize 0
MaxAttempts 5
DatabaseMirror db.fr.clamav.net
DatabaseMirror db.local.clamav.net
DatabaseMirror database.clamav.net
DatabaseDirectory /var/lib/clamav/
DNSDatabaseInfo current.cvd.clamav.net" > /etc/clamav/freshclam.conf
	chown clamav:adm /etc/clamav/freshclam.conf

	## desactivation dans l'interface et scan fixés à aucun
	mysql mysql -h $dbhost -u $dbuser -p$dbpass -D $dbname -e "UPDATE params SET value='1' WHERE name='antivirus';"



# Activation dans l'interfesse
# mysql -h $dbhost -u $dbuser -p$dbpass -D $dbname -e "UPDATE params SET value='1' WHERE name='clamav';"
;;

se3-ocs)
install_module
;;

se3-wpkg)
install_module
;;

se3-unattended)
install_module
;;

se3-fondecran)
SE3MODULE="gsfonts"
M2="imagemagick"

# Paramètres:
chemin_param_fond="/etc/se3/fonds_ecran"

# Création du dossier de paramètres:
mkdir -p $chemin_param_fond
chown www-se3:root $chemin_param_fond

# Dossier de log en cas de mode debug activé:
dossier_log="/var/log/se3/fonds_ecran"
mkdir -p "$dossier_log"

#installation paquets si besoin
install_module && touch $chemin_param_fond/imagemagick_present.txt && touch $chemin_param_fond/gsfonts_present.txt
if [ -e $chemin_param_fond/gsfonts_present.txt ]; then
#paramétrage
echo "Installation ok !, paramétrage...."
echo "3" > $chemin_param_fond/version_samba.txt
touch $chemin_param_fond/actif.txt
chown www-se3 $chemin_param_fond/actif.txt
touch $chemin_param_fond/parametres_generation_fonds.sh
chown www-se3 $chemin_param_fond/parametres_generation_fonds.sh
chmod 750 $chemin_param_fond/parametres_generation_fonds.sh
touch $chemin_param_fond/install_ok.txt
fi

;;
*)
echo "Le module $SE3MODULE n'existe pas ou n'est pas pris en charge par se3 pour le moment" | tee -a $REPORT_FILE
MAIL_REPORT

;;
esac
echo "</pre>"
exit 0

