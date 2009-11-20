#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj110

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
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 110" $MAIL_ADMIN
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

# Recuperation de variables LDAP
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
	echo "Impossible d'accéder au paramètre BASEDN"
	exit 1
fi
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINRDN" ]; then
	echo "Impossible d'accéder au paramètre ADMINRDN"
	exit 1
fi
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINPW" ]; then
	echo "Impossible d'accéder au paramètre ADMINPW"
	exit 1
fi
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PEOPLERDN" ]; then
	echo "Impossible d'accéder au paramètre PEOPLERDN"
	exit 1
fi
GROUPSRDN=`echo "SELECT value FROM params WHERE name='groupsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$GROUPSRDN" ]; then
	echo "Impossible d'accéder au paramètre GROUPSRDN"
	exit 1
fi
RIGHTSRDN=`echo "SELECT value FROM params WHERE name='rightsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$RIGHTSRDN" ]; then
	echo "Impossible d'accéder au paramètre RIGHTSRDN"
	exit 1
fi

PEOPLER=`echo $PEOPLERDN |cut -d = -f 2`
RIGHTSR=`echo $RIGHTSRDN |cut -d = -f 2`
GROUPSR=`echo $GROUPSRDN |cut -d = -f 2`

# Correction nss pour ignorer root et openldap
[ -z "$(grep "nss_initgroups_ignoreusers" /etc/libnss-ldap.conf)" ] && echo "nss_initgroups_ignoreusers root,openldap,plugdev,disk,kmem,tape,audio,daemon,lp,rdma,fuse,video,dialout,floppy,cdrom,tty" >> /etc/libnss-ldap.conf

# Correction de droits
echo "Remise en place des droits - exécution de permse3"
/usr/share/se3/sbin/permse3

# DOMAINSID=`net getlocalsid | cut -d: -f2 | sed -e "s/ //g"`
# ajout dbo pour mappage des groupes de bases
# net groupmap list | grep "\bAdmins\b" ||net groupmap add sid=$DOMAINSID-512 ntgroup=Admins unixgroup=admins type=domain comment="Administrateurs du domaine"
# # net groupmap list | grep "\bEleves\b" || net groupmap add sid=$DOMAINSID-516 ntgroup=Eleves unixgroup=Eleves type=domain comment="Eleves du domaine"
# # net groupmap list | grep "\bProfs\b" || net groupmap add sid=$DOMAINSID-515 ntgroup=Profs unixgroup=Profs type=domain comment="Profs du domaine"
# net groupmap list | grep "\bmachines\b" || net groupmap add sid=$DOMAINSID-553 ntgroup="Domain Computers" unixgroup=machines type=domain comment="Machines du domaine"

# copie nouvelle version sudoers
cp conf/sudoers /etc/sudoers
chmod 0440 /etc/sudoers

# Mise en place conf samba
/usr/share/se3/sbin/update-smbconf.sh

# Mise en place scripts de connexion (defaults)
echo "Mise en place scripts de connexions... (veuillez patienter)"
/usr/share/se3/sbin/update-share.sh -d

# Creation compte adminse3 dans annuaire
/usr/share/se3/sbin/create_adminse3.sh

# Mise a jour cron
cp /var/cache/se3_install/conf/se3-crontab /etc/cron.d/se3
/etc/init.d/cron restart >/dev/null 2>&1

echo "Mise a jour 110:
- Ajout nouvelle architecture de connexion
- Ajout adminse3 dans l'annuaire par defaut
- Ajout corbeille reseau
- Ajout integration vista" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0

