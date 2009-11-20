#!/bin/bash

## $Id$ ##
# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj100

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
	#On envoie un mail � l'admin
	echo "$REPORT"  | mail -s "[SE3] R�sultat de la Mise a jour 100" $MAIL_ADMIN
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
rm -f /var/www/se3/parcs/fonc_parcs.inc.php 

# Correction quotas
echo "alter table quotas CHANGE nom nom VARCHAR(255) NOT NULL" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass

/etc/init.d/slapd stop
# Ajouter DB_CONFIG
cp conf/DB_CONFIG /var/lib/ldap/ 
/etc/init.d/slapd start

# Modif des param�tres apache pour les scripts de cr�ation des comptes
sed -i "s/\(^post_max_size *=*\)\(.*\)\(M.*\)/\116\3/" /etc/php5/apache2/php.ini
sed -i "s/\(^upload_max_filesize *=*\)\(.*\)\(M.*\)/\116\3/" /etc/php5/apache2/php.ini

sed -i "s/\(^max_execution_time *=*\)\(.*\)\(;.*\)/\1300\3/" /etc/php5/cli/php.ini
sed -i "s/\(^memory_limit *= *\)\(.*\)/\132M/" /etc/php5/cli/php.ini

# On relance apache2se
/etc/init.d/apache2se reload

# Correction htpasswd
ETOILE=`cat /var/www/se3/setup/.htpasswd |cut -d: -f2`
if [ "$ETOILE" == "*" ]
then
	/usr/bin/htpasswd -cb /root/testpass admin $password
fi

# Correction de droits
echo "Remise en place des droits - ex�cution de permse3"
/usr/share/se3/sbin/permse3

# Mise a jour du journal des mises a jour

echo "Mise a jour 100:">> $HISTORIQUE_MAJ
echo "- Correction bug cr�ation partage et ajout groupes" >> $HISTORIQUE_MAJ
echo "- Modif des param�tres d'apache pour scripts de cr�ation des comptes"  >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise � jour sera envoy�"

exit 0
