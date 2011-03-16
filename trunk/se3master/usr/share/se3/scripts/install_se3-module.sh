#!/bin/bash
#
## $Id$ ##
#
##### Permet d'installer un paquet module se3#####
### franck.molle@ac-rouen.fr


function usage {
	echo "usage: $0 -a -d -h -i -r module"
	echo "       -a :  activation module"
	echo "       -d :  desactivation module"
	echo "       -i :  install module"
	echo "       -h :  show this help"
	echo "       -r : remove module"
	echo "       ex.:  $0 -i se3-dhcp"
	exit $1;
}

while getopts ":adihr" cmd
do
	case $cmd in	
	  a) activate=1 ;;
	  d) desactivate=1;;
	  i) install=1 ;;
	  r) remove=1 ;;
	  h) usage 0 ;;
	  ?) echo "bad option!"
	      usage 1 ;;
	esac
done

# remise a zero compteur pour avoir le module en $1
shift $(($OPTIND-1))


SE3MODULE="$1"
M2="$2"
M3="$3"

. /etc/se3/config_m.cache.sh

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


[ -z "$LC_ALL" ] && LC_ALL=C && export LC_ALL=C 


#debug="-s"
  echo "<pre>"
. /etc/profile 2>/dev/null
MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE | sed -e "s/$xppass/XXXXXX/g")
#cat $REPORT_FILE
	#On envoie un mail a l'admin
	echo "$REPORT"  | mail -s "[SE3] Resultat de $0" $MAIL_ADMIN
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
# principe bien rempompe sur un script de stephane ;)
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
		echo "Tache d'installation de $SE3MODULE initiee en $t1 et il est $t2" | tee -a $REPORT_FILE
		echo "La tache a depasse le delai imparti." | tee -a $REPORT_FILE
		echo "Le fichier va etre reinitialise..." | tee -a $REPORT_FILE
	else
		echo "Une installation semble deja en cours, veuillez patienter qu'elle se termine, celle-ci dispose de 20mn pour le faire" | tee -a $REPORT_FILE
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
echo "Installation ou MAJ de $SE3MODULE" | tee -a $REPORT_FILE
echo "Mise a jour de la liste des paquets disponibles ....." | tee -a $REPORT_FILE
LINE_TEST
TEST_LOCK
apt-get update -qq && (echo "Liste mise a jour avec succes" | tee -a $REPORT_FILE)
echo "" | tee -a $REPORT_FILE

echo "Installation du paquet $SE3MODULE et de ses dependances" | tee -a $REPORT_FILE
LC_ALL=C apt-get install $SE3MODULE -y --force-yes $opt | tee -a $REPORT_FILE
if [ ! -z "$M2" ]; then
echo "Installation du paquet complementaire $M2" | tee -a $REPORT_FILE
LC_ALL=C apt-get install $M2 -y --force-yes $opt | tee -a $REPORT_FILE
fi

if [ ! -z "$M3" ]; then
echo "Installation du paquet complementaire $M3" | tee -a $REPORT_FILE
LC_ALL=C apt-get install $M3 -y --force-yes $opt | tee -a $REPORT_FILE
fi

# L'envoi d'un mail est superflu
#MAIL_REPORT
}

remove_module()
{
echo "Supression de $SE3MODULE" | tee -a $REPORT_FILE
apt-get remove $SE3MODULE -y | tee -a $REPORT_FILE
}

if [ "$nomaj" == "1" ]; then
echo "systeme de maj non disponible pour le moment"
exit 0
fi


## on installe quoi comme module ?
case "$1" in
se3)
install_module
#echo "<br/>"
#se3domain_ok=$(dpkg -s se3-domain | grep "Status: install ok")
#if [ -z "$se3domain_ok" ]; then
#  echo ""
  echo "Maj si besoin se3-domain" | tee -a $REPORT_FILE
  apt-get install se3-domain -y --force-yes $opt | tee -a $REPORT_FILE 
  
#fi

# UPDATE PARTIE MODULES 
(
dpkg -l|grep se3-|cut -d ' ' -f3|while read package
do
LC_ALL=C apt-get -s install $package|grep newest >/dev/null|| echo $package
done
)>/root/se3_update_list

list_module=$(cat /root/se3_update_list)
if [ -n "$list_module" ]; then
  echo ""
  echo "Mise a jour des modules" | tee -a $REPORT_FILE
  apt-get install $list_module -y --force-yes $opt 2>&1 | tee -a $REPORT_FILE 
##
##apt-get install $(cat /tmp/se3_update_list) --allow-unauthenticated -y -o Dpkg::Options::=--force-confold 2>&1 | tee -a $REPORT_FILE 
#  rm -f /root/se3_update_list
  
fi

MAIL_REPORT
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
		echo -e "Presence de /tftpboot detectee, se3-clonage a renomme le repertoire en /tftpboot_${SE3MODULE}.sav" | tee -a $REPORT_FILE
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

	echo "Recuperation du paquet se3-clamav si necessaire (activation possible via l'interface)"
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



# Activation dans l'interface
# mysql -h $dbhost -u $dbuser -p$dbpass -D $dbname -e "UPDATE params SET value='1' WHERE name='clamav';"
;;

se3-ocs)
cp /root/.my.cnf  /var/remote_adm/
chown www-se3 /var/remote_adm/.my.cnf
chmod 400 /var/remote_adm/.my.cnf
install_module
rm -f /var/remote_adm/.my.cnf
;;

se3-wpkg)
install_module
;;

se3-unattended)
install_module
;;

se3-domain)
install_module
;;

se3-logonpy)
install_module
;;

se3-internet)
install_module
;;

se3-backup)
install_module
;;

se3-synchro)

if [ "$install" ==  "1" ]; then
  install_module
fi

if [ "$remove" ==  "1" ]; then
  remove_module
fi

;;
se3-seven)
echo "Installation ou MAJ du support seven (samba backport)" | tee -a $REPORT_FILE
LINE_TEST
TEST_LOCK
echo "deb http://backports.debian.org/debian-backports lenny-backports main" > /etc/apt/sources.list.d/smb_backport.list
echo "Mise a jour de la liste des paquets disponibles ....." | tee -a $REPORT_FILE
apt-get update -qq && (echo "Liste mise a jour avec succes" | tee -a $REPORT_FILE)
echo "" | tee -a $REPORT_FILE

echo "Installation du paquet Samba et de ses dependances" | tee -a $REPORT_FILE
echo "Dpkg::Options {\"--force-confnew\";}" > /etc/apt/apt.conf
apt-get -t lenny-backports install samba -y --force-yes $opt 2>&1 | tee -a $REPORT_FILE 
apt-get -t lenny-backports install samba-common-bin -y --force-yes $opt 2>&1 | tee -a $REPORT_FILE 
rm -f /etc/apt/apt.conf
MAIL_REPORT
;;

se3-fondecran)
SE3MODULE="gsfonts"
M2="imagemagick"

# Parametres:
chemin_param_fond="/etc/se3/fonds_ecran"

# Creation du dossier de parametres:
mkdir -p $chemin_param_fond
chown www-se3:root $chemin_param_fond

# Dossier de log en cas de mode debug active:
dossier_log="/var/log/se3/fonds_ecran"
mkdir -p "$dossier_log"

#installation paquets si besoin
install_module && touch $chemin_param_fond/imagemagick_present.txt && touch $chemin_param_fond/gsfonts_present.txt
if [ -e $chemin_param_fond/gsfonts_present.txt ]; then
#paramétrage
echo "Installation ok !, parametrage...."
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
echo "Installation terminee, suppression du fichier verrou" | tee -a $REPORT_FILE
rm -f $fich_lock
/usr/share/se3/scripts/refresh_cache_params.sh
exit 0

