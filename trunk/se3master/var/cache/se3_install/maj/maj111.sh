#!/bin/bash

## $Id: maj111.sh 4953 2009-11-22 23:25:28Z keyser $ ##

# path fichier de logs
LOG_DIR="/var/log/se3"
HISTORIQUE_MAJ="$LOG_DIR/historique_maj"
REPORT_FILE=$LOG_DIR/log_maj111

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
	echo "$REPORT"  | mail -s "[SE3] Résultat de la Mise a jour 111" $MAIL_ADMIN
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


# Modif table connexions
# echo "Correction table connexions"
# mysql -h $dbhost $dbname -u $dbuser -p$dbpass < maj/connexions.sql
# echo "alter table connexions change id id bigint(20)" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass

# Modif logrotate.d/samba
echo "Rotation des logs samba si plus de 50Mo"
echo "#rotation des logs si plus de 50Mo
/var/log/samba/ {
        weekly
        missingok
        rotate 2
        postrotate
                invoke-rc.d --quiet samba reload > /dev/null
        endscript
        size 50M
        compress
        notifempty
}" >> /etc/logrotate.d/samba

# correction php-cli pour gros bahuts
sed -i "s/\(^max_execution_time *=*\)\(.*\)\(;.*\)/\1600\3/" /etc/php5/cli/php.ini
sed -i "s/\(^memory_limit *= *\)\(.*\)/\164M/" /etc/php5/cli/php.ini


if [ -e /var/www/se3.pac ]; then
echo "Correction se3.pac en arriere plan dans 1mn"
AT_SCRIPT="/root/correct_se3pac.sh"
echo '
#!/bin/bash
SE3IP=$(/sbin/ifconfig eth0 | grep inet |cut -d : -f 2 |cut -d \  -f 1| head -n 1)
for user in $(ls /home | grep -v netlogon | grep -v templates)
do
  PREF_JS="/home/$user/profil/appdata/Mozilla/Firefox/Profiles/default/prefs.js"
  [ -e "$PREF_JS" ] && sed "s%http:///se3.pac%http://$SE3IP/se3.pac%" -i "$PREF_JS"
done
exit 0' > $AT_SCRIPT
      chmod 700 $AT_SCRIPT
      at now +1 minutes -f $AT_SCRIPT
fi

# installation supervision pour les serveurs de rouen uniquement
./depmaj/install_supervision_rouen.sh

# Correction de droits
# echo "Remise en place des droits - execution de permse3"
# /usr/share/se3/sbin/permse3

echo "Mise a jour 111:
- Correctif rotation logs samba
- Correction bug sur mkhome.pl pour se3.pac" >> $HISTORIQUE_MAJ
 
MAIL_REPORT
echo "Un mail recapitulatif de la mise à jour sera envoyé"

exit 0
