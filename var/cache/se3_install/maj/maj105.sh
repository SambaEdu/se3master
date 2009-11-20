#!/bin/bash

## $Id$ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj105

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
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 105" $MAIL_ADMIN
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

echo "alter table restrictions CHANGE valeur valeur VARCHAR(255) NOT NULL" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass

# Relance le script pour les clients Linux
/usr/share/se3/sbin/create_client_linux.sh

# supression script fond ecran
rm -f /usr/share/se3/scripts/fonds_modif_smb.sh
rm -f usr/share/se3/scripts/install_imagemagick_et_gsfonts.sh
# vieux script
rm -f /usr/share/se3/scripts/svg_se3_v3.sh

# Mise a jour crontab pour se3
cp conf/se3-crontab /etc/cron.d/se3
/etc/init.d/cron restart

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

echo  "Ajout du droit no_Trash_user pour eviter la suppression de comptes comme le/la documentaliste"
cat ldif/rights_maj7.ldif | sed -e "s/#BASEDN#/$BASEDN/g" | sed -e "s/#RIGHTS#/$RIGHTSR/g" | sed -e "s/#GROUPS#/$GROUPSR/g" | sed -e "s/#PEOPLE#/$PEOPLER/g" | ldapadd -x -D "$ADMINRDN,$BASEDN" -w $ADMINPW


if [ ! -e "/home/templates/skeluser/profil/appdata/Thunderbird/Profiles/default/prefs.js.lcs_ssl" ]
then
cp -av /home/templates/skeluser/profil/appdata/Thunderbird/Profiles/default/prefs.js.lcs /home/templates/skeluser/profil/appdata/Thunderbird/Profiles/default/prefs.js.lcs_ssl

 sed 's/user_pref("mail.server.server2.type", "imap");/user_pref("mail.server.server2.type", "imap");\r\nuser_pref("mail.server.server2.port", 143);\r\nuser_pref("mail.server.server2.socketType", 1);/' -i /home/templates/skeluser/profil/appdata/Thunderbird/Profiles/default/prefs.js.lcs
else 
echo "/home/templates/skeluser/profil/appdata/Thunderbird/Profiles/default/prefs.js.lcs_ssl existe deja"
fi

echo "Modification smb.conf pour nouvelle version de machineAdd.pl" 
[ -z "$(grep "machineAdd.pl %u %I" /etc/samba/smb.conf)" ] && sed 's/machineAdd.pl %u/machineAdd.pl %u %I/' -i /etc/samba/smb.conf
[ -e "/etc/samba/smb_WinXP.conf" ] &&  [ -z "$(grep "machineAdd.pl %u %I" /etc/samba/smb_WinXP.conf)" ] && sed 's/machineAdd.pl %u/machineAdd.pl %u %I/' -i /etc/samba/smb_WinXP.conf
[ -e "/etc/samba/smb_Win2K.conf" ] &&  [ -z "$(grep "machineAdd.pl %u %I" /etc/samba/smb_Win2K.conf)" ] && sed 's/machineAdd.pl %u/machineAdd.pl %u %I/' -i /etc/samba/smb_Win2K.conf


# Correction de droits
echo "Remise en place des droits - exécution de permse3"
/usr/share/se3/sbin/permse3





# DOMAINSID=`net getlocalsid | cut -d: -f2 | sed -e "s/ //g"`
# ajout dbo pour mappage des groupes de bases
# net groupmap list | grep "\bAdmins\b" ||net groupmap add sid=$DOMAINSID-512 ntgroup=Admins unixgroup=admins type=domain comment="Administrateurs du domaine"
# # net groupmap list | grep "\bEleves\b" || net groupmap add sid=$DOMAINSID-516 ntgroup=Eleves unixgroup=Eleves type=domain comment="Eleves du domaine"
# # net groupmap list | grep "\bProfs\b" || net groupmap add sid=$DOMAINSID-515 ntgroup=Profs unixgroup=Profs type=domain comment="Profs du domaine"
# net groupmap list | grep "\bmachines\b" || net groupmap add sid=$DOMAINSID-553 ntgroup="Domain Computers" unixgroup=machines type=domain comment="Machines du domaine"

# Mise a jour du journal des mises a jour
echo "Mise a jour 105:
- fond ecran géré en module avec la page conf_module
- nouveau droit no_trash
- modifification structurelle de machineAdd.pl
- Remplacement de connexion.pl par connexion.sh, correction des macs incorrectes
- Corrections diverses
- Modification / optimisation page test.php (mrT)
- optimisation page action sur parc (mrT)
- Correction italc_generate / partie Nas
- Optimisations scripts quotas" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0
