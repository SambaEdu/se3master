#!/bin/bash

## $Id$ ##

# Suppression avant Maj du répertoire /var/log/se3 qui a un problème de rotation des logs
rm -Rf /var/log/se3
mkdir /var/log/se3
chown www-se3 /var/log/se3
perl -pi -e "s&/var/log/se3/\*&/var/log/se3/*.log&" /etc/logrotate.d/se3

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj101

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
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 100" $MAIL_ADMIN
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

[ ! -e /usr/lib/libmysqlclient.so.12 ] && ln -s /usr/lib/libmysqlclient.so.15 /usr/lib/libmysqlclient.so.12


#Suppression script venant de la version Sarge
SCRIPTS_DIR="/usr/share/se3/sbin"
# rm -f $SCRIPTS_DIR/se3_upgrade_etch.sh 
rm -f $SCRIPTS_DIR/migration_ldap_etch.sh 

# supression sscript fond ecran
rm -f /usr/share/se3/scripts/fonds_modif_smb.sh

# suppression page inutile
rm -f /var/www/se3/parcs/fonc_parcs.inc.php >/dev/null 



# Correction htpasswd
rm -f /root/testpass >/dev/null
ETOILE=`cat /var/www/se3/setup/.htpasswd |cut -d: -f2`
if [ "$ETOILE" == "*" ]
then
	/usr/bin/htpasswd -cb /var/www/se3/setup/.htpasswd admin $password
fi

/etc/init.d/slapd stop
# Ajouter DB_CONFIG
cp conf/DB_CONFIG /var/lib/ldap/ 
/etc/init.d/slapd start

# Correction de droits
echo "Remise en place des droits - exécution de permse3"
/usr/share/se3/sbin/permse3

# Mise a jour du journal des mises a jour

echo "Mise a jour 101:">> $HISTORIQUE_MAJ
echo "- Correction bug création partage et suppression partage" >> $HISTORIQUE_MAJ
echo "- Modif mkslapd.conf en raison d'un bug"  >> $HISTORIQUE_MAJ
echo "- Correction d'un bug dans /var/log/se3"  >> $HISTORIQUE_MAJ
echo "- Correction printer_jobs" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0
