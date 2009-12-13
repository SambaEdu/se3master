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

# Modification de sudoers
# separation de la conf BACKUP dans sudoers
#


mysql -f se3db < /var/cache/se3_install/se3db.sql 2>/dev/null

CUR_USER=`grep "USER=" /etc/init.d/backuppc |cut -d= -f2`
BCK_USER=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='bck_user'"`
if [ -z "$BCK_USER" ] ; then
BCK_USER="$CUR_USER"
echo "UPDATE params SET value=\"$BCK_USER\" WHERE name='bck_user'"| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
fi


# [ -z "$CUR_USER" ] && CUR_USER="backuppc"

BCK_UIDNUMBER=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='bck_uidnumber'"`

if [ -z "$BCK_UIDNUMBER" ]; then
BPCN=`getent passwd backuppc | cut -d : -f3`

      if [ -z "$BPCN" ]; then
	    BPCN="104"
	    echo "UPDATE params SET value=\"104\" WHERE name='bck_uidnumber'"| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
	    sed "/backuppc:x.*/d" -i /etc/passwd
	    echo 'backuppc:x:104:106:BackupPC,,,:/var/lib/backuppc:/bin/sh' >> /etc/passwd
      else
	    echo "UPDATE params SET value=\"$BPCN\" WHERE name='bck_uidnumber'"| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
      fi
fi
grep BACKUP1 /etc/sudoers>/dev/null && BCK=1
if [ ! "BCK" = "1" ]; then
	echo "Scindage de la config BACKUP dans sudoers"
	rm -f /tmp/fsu*
	split -l 14 /etc/sudoers /tmp/fsu
	mv /tmp/fsuaa /etc/sudoers.new
	echo "Cmnd_Alias BACKUP = /usr/share/se3/scripts/startbackup, /usr/share/se3/scripts/move_rep_backuppc.sh, /usr/share/se3/sbin/testbackup.sh, /usr/share/se3/sbin/diskdetect.sh, /usr/share/se3/sbin/restorebackup.sh, /usr/share/se3/sbin/umountusbdisk.sh, /usr/share/se3/scripts/mk_rsyncconf.sh, /usr/share/se3/scripts/dfbck.sh, /usr/share/se3/scripts/mountown.sh, /usr/share/se3/sbin/chgbpcuser.sh">> /etc/sudoers.new
	echo "Cmnd_Alias BACKUP1 = /usr/share/se3/scripts/tarCreate, /usr/share/se3/scripts/tarRestore" >> /etc/sudoers.new
	cat /tmp/fsu* | grep -v "BACKUP" >> /etc/sudoers.new
	tail /etc/sudoers |grep "BACKUP," >> /etc/sudoers.new
	echo "$BCK_USER ALL=NOPASSWD:BACKUP1" >> /etc/sudoers.new
	cp /etc/sudoers /etc/sudoers.se3sav
	cp /etc/sudoers.new /etc/sudoers
	chmod 440 /etc/sudoers
fi



if [ "$BCK_USER" != "$CUR_USER" ]; then
	echo "Modification de la config backuppc"
	sed -i "s/USER=$CUR_USER/USER=$BCK_USER/g" /etc/init.d/backuppc
	BADLINE=`grep "BackupPCUser}" /etc/backuppc/config.pl | cut -c 2-`
	GOODLINE=`echo $BADLINE |sed -e "s/$CUR_USER/$BCK_USER/g" `
	sed -i "s/$BADLINE/$GOODLINE/g" /etc/backuppc/config.pl
	#BADLINE=`grep "CgiAdminUsers}" /etc/backuppc/config.pl | cut -c 2-`
	#GOODLINE=`echo $BADLINE |sed -e "s/$CUR_USER/$BCK_USER/g" `
	#sed -i "s/$BADLINE/$GOODLINE/g" /etc/backuppc/config.pl
	sed -i "s/$CUR_USER ALL=NOPASSWD:BACKUP1/$BCK_USER ALL=NOPASSWD:BACKUP1/g" /etc/sudoers
	chmod 440 /etc/sudoers
fi

# Retablissement des droits ---> arriere plan pour plus de rapidité

echo "Retablissement des droits en arriere plan dans 1mn"
AT_SCRIPT="/root/droits_bpc.sh"
echo "
#!/bin/bash
chown $BCK_USER.www-data /etc/backuppc
chmod 775 /etc/backuppc
chown $BCK_USER:www-data /etc/backuppc/hosts
chmod 775 /etc/backuppc/hosts
chown $BCK_USER.www-data /etc/SeConfig.ph
chmod 640 /etc/SeConfig.ph
chown $BCK_USER.www-data /etc/backuppc/*.pl
chown $BCK_USER /usr/share/backuppc/cgi-bin/index.cgi
chmod u+s /usr/share/backuppc/cgi-bin/index.cgi
chown $BCK_USER /var/lib/backuppc -R
chown $BCK_USER /var/run/backuppc -R
rm -f /root/droits_bpc.sh" > $AT_SCRIPT
chmod 700 $AT_SCRIPT
at now +1 minutes -f $AT_SCRIPT

# ajout de www-se3 a www-data
adduser www-se3 www-data 2&>/dev/null

# restart sudo
/etc/init.d/sudo restart


# Correction de droits
echo "Remise en place des droits - ex¿cution de permse3"
/usr/share/se3/sbin/permse3

echo "Mise a jour 110:
- Correctif backuppc / NAS" >> $HISTORIQUE_MAJ
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0
