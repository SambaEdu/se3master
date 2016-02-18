#!/bin/bash
#
# Franck Molle
# distribue sous licence GPL
# 12/2005
#
##### Permet de vider la table session et delester connexions de se3db afin d'eviter le debordement, script lancé une fois / mois par cron #####
##$Id$##
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "permet de vider la table session  et nettoyer la table connexions de la base mysql se3bd"
	echo "Ce script est lance une fois par mois en crontab /etc/crontab.monthly"
	echo ""
	echo "Usage : aucune option"
	exit
fi	





WWWPATH="/var/www"
echo -e "$COLCMD\c"
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo -e "$COLERREUR"
	echo "Fichier de conf inaccessible."
	echo -e "$COLTXT"
	exit 1
fi
## vidage de la table session
echo "Vidage de la table session"
echo "TRUNCATE sessions"|mysql -h $dbhost $dbname -u $dbuser -p$dbpass

echo "delestage de la table connexions"
echo "delete from connexions where logintime<date_sub(now(),INTERVAL 90 DAY)"|mysql -h $dbhost $dbname -u $dbuser -p$dbpass

exit 0
