#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj109

#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)

### recup pass root mysql
. /root/.my.cnf 2>/dev/null

MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail à l'admin
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 109" $MAIL_ADMIN
fi
}


POURSUIVRE()
{
	[ -n "$1" ] && echo "$1"
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "O" -a "$REPONSE" != "n" ]
	do
		#echo -e "$COLTXT"
		echo -e "${COLTXT}Peut-on poursuivre ? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done
	echo -e "$COLTXT"
	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		echo "Abandon!"
		exit 1
	fi
}

# Correction du bug de printers_group.pl corrigé le 31/01/2009 : correction non effective si on ne relance pas le script
/usr/share/se3/sbin/printers_group.pl


##### Install unattended 4.7

# remplacement d'unattended 4.6
mkdir -p /var/se3/unattended/install/oldunattended
# Mise au chaud des vieux fichiers pour ceux qui auraient utilisé unattend 4.6 : ils pourront récupérer...
[ -e /var/se3/unattended/bootdisk ] && [ ! -e /var/se3/unattended/install/oldunattended/bootdisk ] && mv /var/se3/unattended/bootdisk /var/se3/unattended/install/oldunattended/bootdisk
[ -e /var/se3/unattended/linuxboot ] && [ ! -e /var/se3/unattended/install/oldunattended/linuxboot ] && mv /var/se3/unattended/linuxboot /var/se3/unattended/install/oldunattended/linuxboot
[ -e /var/se3/unattended/install/bin ] && [ ! -e /var/se3/unattended/install/oldunattended/bin ] && mv /var/se3/unattended/install/bin /var/se3/unattended/install/oldunattended/bin
[ -e /var/se3/unattended/install/dosbin ] && [ ! -e /var/se3/unattended/install/oldunattended/dosbin ] && mv /var/se3/unattended/install/dosbin /var/se3/unattended/install/oldunattended/dosbin
[ -e /var/se3/unattended/install/lib ] && [ ! -e /var/se3/unattended/install/oldunattended/lib ] && mv /var/se3/unattended/install/lib /var/se3/unattended/install/oldunattended/lib
[ -e /var/se3/unattended/install/scripts ] && [ ! -e /var/se3/unattended/install/oldunattended/scripts ] && mv /var/se3/unattended/install/scripts /var/se3/unattended/install/oldunattended/scripts
[ -e /var/se3/unattended/install/site ] && [ ! -e /var/se3/unattended/install/oldunattended/site ] && mv /var/se3/unattended/install/site /var/se3/unattended/install/oldunattended/site
[ -e /var/se3/unattended/install/tools ] && [ ! -e /var/se3/unattended/install/oldunattended/tools ] && mv /var/se3/unattended/install/tools /var/se3/unattended/install/oldunattended/tools

UNATTENDED_DIR="/var/cache/se3_install/unattended"
UNATTENDED_VER="unattended-4.7.zip"
UNATTENDED_MD5="unattended-4.7.md5"

mkdir -p $UNATTENDED_DIR
cd $UNATTENDED_DIR
[ -e "$UNATTENDED_VER" ] && rm "$UNATTENDED_VER"
[ -e "$UNATTENDED_MD5" ] && rm "$UNATTENDED_MD5"

wget http://wawadeb.crdp.ac-caen.fr/unattended/$UNATTENDED_VER
wget http://wawadeb.crdp.ac-caen.fr/unattended/$UNATTENDED_MD5
if [ "$(cat $UNATTENDED_MD5 | awk '{print $1}')" != "$(md5sum $UNATTENDED_VER | awk '{print $1}')" ]; then
echo "Somme Md5 de l'image téléchargee invalide"
echo "Veuillez télécharger l'archive $UNATTENDED_VER sur l'url suivante :"
echo "http://wawadeb.crdp.ac-caen.fr/unattended/$UNATTENDED_VER"
echo 'Decompressez la puis Deposez la ensuite sur le serveur sur \\se3\install\'
else

# Decompression des fichiers unattended 4.7
cd /var/se3/unattended/install
unzip -o -q /var/cache/se3_install/unattended/unattended-4.7.zip

# la crontab permet l'actualisation le soir (en attendant de modifier les pages parcs pour qu'elles exécutent unattended-generate.sh en cas de modif)
cp /var/cache/se3_install/conf/se3-crontab /etc/cron.d/se3
/etc/init.d/cron restart > /dev/null

# ajout du droit x sur ce fichier car unattended-generate.sh (ci-dessus) en a besoin
setfacl -m u::rwx -m g::rx -m o::rx /var/se3/unattended/install/tools/prepare
# création du unattend.csv initial à partir de l'annuaire se3 actuel et téléchargement des fichiers nécessaires : activeperl, msinstaller,...
[ -e /usr/share/se3/scripts/makedhcpdconf ] && /usr/share/se3/scripts/unattended_generate.sh 
cd /var/cache/se3_install
fi
# Mise a jour du journal des mises a jour

[ -z "$(grep "bind_policy soft" /etc/libnss-ldap.conf)" ] && echo "bind_policy soft" >> /etc/libnss-ldap.conf

echo "Relance le script pour les clients Linux - Ajout test lancement sur serveur"
/usr/share/se3/sbin/create_client_linux.sh >/dev/null
echo ""

#Correction bug gsfont si besoin
AT_SCRIPT="/root/verif_fondecran.sh"
echo '
#!/bin/bash
[ -n "$(dpkg -s gsfonts | grep "install ok installed")" ] && apt-get install imagemagick -y  
rm -f /root/verif_fondecran.sh' > $AT_SCRIPT
chmod 700 $AT_SCRIPT
at now +1 minutes -f $AT_SCRIPT

# Correction de droits
echo "Remise en place des droits - ex¿cution de permse3"
/usr/share/se3/sbin/permse3


echo "Mise a jour 109:
- Ajout des scripts unattended 4.7" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0
