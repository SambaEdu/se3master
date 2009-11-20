#!/bin/bash

## $Id$ ##


# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj102

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
	echo "$REPORT"  | mail -s "[SE3] Resultat de la Mise a jour 102" $MAIL_ADMIN
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

# supression script fond ecran
rm -f /usr/share/se3/scripts/fonds_modif_smb.sh


# Correction htpasswd
rm -f /root/testpass >/dev/null
ETOILE=`cat /var/www/se3/setup/.htpasswd |cut -d: -f2`
if [ "$ETOILE" == "*" ]
then
	/usr/bin/htpasswd -cb /var/www/se3/setup/.htpasswd admin $password
fi

#mise à jour de la crontab
cp conf/se3-crontab /etc/cron.d/se3
/etc/init.d/cron restart


FSTAB_TMP="/root/fstab"
FSTAB_ORI="/etc/fstab"
echo "" > $FSTAB_TMP

echo "Modification de fstab pour les quotas..."
while read LIGNE
do
	XFS_DETECT=$(echo $LIGNE | grep xfs)
	if [ "$XFS_DETECT" != "" ]; then
		QUOTAS_OK=$(echo "$LIGNE" | grep "defaults,quota")
		if [ -z "$QUOTAS_OK" ]; then
        		echo "$LIGNE" | sed -e "s/defaults/defaults,quota/" >>  $FSTAB_TMP
		else
			echo "$LIGNE" >> $FSTAB_TMP
		fi

	else
		echo "$LIGNE" >> $FSTAB_TMP
	fi
done < $FSTAB_ORI
mv $FSTAB_ORI ${FSTAB_ORI}.sauve_maj102
mv $FSTAB_TMP $FSTAB_ORI
echo "Le fichier fstab a ete modifie pour l'activation des quotas
Ce changement ne sera effectif qu'au prochain reboot du serveur.
Toutefois, si voulez que ce soit pris en compte immediatement 
tapez \"install_quotas.sh\" en console cela aura pour effet de demonter / remonter
les partitions /home et /var/se3 et relancera samba. Veillez a ne pas le faire
aux heures de pointe ;)"
sleep 3


#mise à jour de la crontab
cp conf/se3-crontab /etc/cron.d/se3
/etc/init.d/cron restart

# Modif sudoers
if [ ! "$(grep "/usr/share/se3/scripts/mv_Trash_Home.sh" /etc/sudoers)" ]; then
		# On ajoute la commande au premier rang
		sed -i 's|Cmnd_Alias LDAPCLEAN =|Cmnd_Alias LDAPCLEAN = /usr/share/se3/scripts/mv_Trash_Home.sh,|' /etc/sudoers
		
fi
if [ ! "$(grep "/usr/share/se3/scripts/italc_generate.sh" /etc/sudoers)" ]; then
		# On ajoute la commande au premier rang
		sed -i 's|Cmnd_Alias SE3APPLI =|Cmnd_Alias SE3APPLI = /usr/share/se3/scripts/italc_generate.sh,|' /etc/sudoers
		
fi
/etc/init.d/sudo restart


# Correction de droits
echo "Remise en place des droits avec permse3"
/usr/share/se3/sbin/permse3

# Mise a jour du journal des mises a jour

echo "Mise a jour 102:">> $HISTORIQUE_MAJ
echo "- Correction $_GET dans pages registre/mod_export.php " >> $HISTORIQUE_MAJ
echo "- Correction $_GET dans pages registre/affiche_modele.php"  >> $HISTORIQUE_MAJ
echo "- Ajout devel pour italc (Beta)" >> $HISTORIQUE_MAJ
echo "- Correction du bug sur les quotas" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise a jour sera envoye"

exit 0
