#!/bin/bash

## $Id: maj133.sh 5921 2010-11-06 14:14:02Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE="$LOG_DIR/log_maj133"
#mode debug on si =1
[ -e /root/debug ] && DEBUG="1"

#date
LADATE=$(date +%d-%m-%Y)

rm -f /etc/apt/apt.conf.d/02se3
# insert donnees manquantes si besoin
mysql -u $dbuser -p$dbpass -f se3db < /var/cache/se3_install/se3db.sql 2>/dev/null


#ajout fichier si ligne manquante wine ds sources.list
if [ -n "$(grep "^5." /etc/debian_version)" ]; then
	if [ -z "$(grep "http://www.lamaresh.net/apt" /etc/apt/sources.list)" ]; then
		echo "# sources wine utiles pour AMD64" > /etc/apt/sources.list.d/wine.list
		echo "deb http://www.lamaresh.net/apt lenny main" >> /etc/apt/sources.list.d/wine.list
		wget http://www.lamaresh.net/apt/key.gpg && apt-key add key.gpg
	fi
fi


#Correction bug lenny libnss udev
addgroup --system nvram 2>/dev/null
addgroup --system tss 2>/dev/null
addgroup --system fuse 2>/dev/null
addgroup --system kvm 2>/dev/null
addgroup --system rdma 2>/dev/null
adduser --system --no-create-home --ingroup tss tss 2>/dev/null

echo "ALTER TABLE params CHANGE descr descr VARCHAR( 100 )" | mysql $dbname -u $dbuser -p$dbpass

SETMYSQL distribution lenny "Version de la distribution debian" 5


/etc/init.d/slapd stop
# Ajouter DB_CONFIG
cp conf/DB_CONFIG /var/lib/ldap/ 
/etc/init.d/slapd start
sleep 3

#=======================================
# Fond d ecran personnalise
# t=$(grep "^Cmnd_Alias FONDS_ECRAN" /etc/sudoers|grep "/usr/share/se3/sbin/mkwall.sh")
# if [ -z "$t" ]; then
# 	sed -ri "s|^(Cmnd_Alias FONDS_ECRAN.*)|\1, /usr/share/se3/sbin/mkwall.sh|" /etc/sudoers
# 	/etc/init.d/sudo restart
# fi

# . /usr/share/se3/includes/config.inc.sh -cl
echo "dn: cn=fond_can_change,${rightsRdn},${ldap_base_dn}
objectClass: groupOfNames
cn: fond_can_change
member: uid=admin,${peopleRdn},${ldap_base_dn}
" | ldapadd -x -D ${adminRdn},${ldap_base_dn} -w ${adminPw}

mkdir -p /var/www/se3/Admin/fonds_ecran/courant
chown www-se3 /var/www/se3/Admin/fonds_ecran/courant

mkdir -p /var/lib/se3/fonds_ecran
chown www-se3 /var/lib/se3/fonds_ecran
#=======================================

echo "Mise a jour 133:
- Alter table params pour passer champ description a 100 caracteres
- Ajout entree distribution dans la bdd
- Ajout des groupes nécessaires pour udev si besoin lors du boot
- modif DB_CONFIG pour migration future sqeeze" >> $HISTORIQUE_MAJ

exit 0
