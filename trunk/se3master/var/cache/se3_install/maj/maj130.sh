#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj130"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)

# insert donnees manquantes si besoin
mysql -u $dbuser -p$dbpass -f se3db < /var/cache/se3_install/se3db.sql 2>/dev/null


# install si besoin supervision rouen 
./depmaj/install_supervision_rouen.sh

#Ménage du coté backuppc si necessaire
if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /etc/backuppc/config.pl)" ]; then
rm -f /etc/backuppc/config.pl.divert
dpkg-divert --package se3 --remove --rename /etc/backuppc/config.pl 
fi 

if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /etc/backuppc/localhost.pl)" ]; then
rm -f /etc/backuppc/localhost.pl.divert
dpkg-divert --package se3 --remove --rename /etc/backuppc/localhost.pl 
fi 

if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /etc/backuppc/apache.conf)" ]; then
rm -f /etc/backuppc/apache.conf.divert
dpkg-divert --package se3 --remove --rename /etc/backuppc/apache.conf

fi 

if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /usr/share/backuppc/image/BackupPC_stnd.css)" ]; then
rm -f /usr/share/backuppc/image/BackupPC_stnd.css.divert
dpkg-divert --package se3 --remove --rename /usr/share/backuppc/image/BackupPC_stnd.css 
fi 

if [ ! -z "$(dpkg-divert --list | grep "se3$" | grep /usr/share/backuppc/lib/BackupPC/CGI/Lib.pm)" ]; then
rm -f /usr/share/backuppc/lib/BackupPC/CGI/Lib.pm.divert 
dpkg-divert --package se3 --remove --rename /usr/share/backuppc/lib/BackupPC/CGI/Lib.pm
fi 




echo "mise a jour sources.list SE3....Patientez svp"
mv /etc/apt/sources.list /etc/apt/sources.list_maj130

cat >/etc/apt/sources.list <<END

# Sources standard:
deb http://ftp.fr.debian.org/debian/ lenny main non-free contrib
deb-src http://ftp.fr.debian.org/debian/ lenny main non-free contrib

# Security Updates:
deb http://security.debian.org/ lenny/updates main contrib non-free

# entree pour clamav derniere version
deb http://ftp2.de.debian.org/debian-volatile lenny/volatile main

# sources wine utiles pour AMD64
deb http://www.lamaresh.net/apt lenny main

# sources pour se3
deb ftp://wawadeb.crdp.ac-caen.fr/debian lenny se3

#### Sources testing desactivee en prod ####
#deb ftp://wawadeb.crdp.ac-caen.fr/debian lenny se3testing

#### Sources XP desactivee en prod ####
#deb ftp://wawadeb.crdp.ac-caen.fr/debian lenny se3XP
END

wget http://www.lamaresh.net/apt/key.gpg && apt-key add key.gpg

#Correction bug lenny libnss udev
addgroup --system nvram
addgroup --system tss
addgroup --system fuse
addgroup --system kvm
addgroup --system rdma
adduser --system --no-create-home --ingroup tss tss 

#Modif annuaire samba pour Lenny
/usr/share/se3/sbin/migrationLenny.sh 


net groupmap list | grep -q "machines" || net groupmap add ntgroup="machines" rid="515" unixgroup="machines" type="domain"
net groupmap list | grep -q "lcs-users" || net groupmap add ntgroup="Utilisateurs du domaine" rid="513" unixgroup="lcs-users" type="domain"

echo "mappage des groupes pour samba en cours.....Patientez"
ldapsearch -xLLL -h $ldap_server -b $groupsRdn,$ldap_base_dn "(&(objectClass=posixGroup)(!(objectClass=sambaGroupMapping)))" cn | grep "^cn:"  | cut -c 5- | while read cn; do
    /usr/share/se3/scripts/group_mapping.sh $cn >> $REPORT_FILE
done
echo "Mappage Terminé"


echo "Mise a jour 130:
- Correction bug lenny libnss udev
- supervision rouen
- Menage divert backuppc
- Modif sources.list pour lenny
- Correction bug lenny libnss udev
- Modif annuaire samba pour Lenny
- mappage des groupes pour samba" >> $HISTORIQUE_MAJ

exit 0
