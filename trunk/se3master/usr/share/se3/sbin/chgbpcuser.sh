#!/bin/bash

if [ -e /var/www/se3/includes/config.inc.php ]
then
        dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "impossible d'acceder aux params mysql"
        exit 1
fi

bck_user=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='bck_user'"`

cur_user=`grep "USER=" /etc/init.d/backuppc |cut -d= -f2`

if [ "$bck_user" = "backuppc" ]; then

#
# Modification de l'uidNumber de bckuppc
#

echo "Modification de l'uidNumber de bckuppc"

BPCN=`getent passwd backuppc | cut -d : -f3`
bck_uidnumber=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='bck_uidnumber'"`

sed -i "s/backuppc:x:$BPCN/backuppc:x:$bck_uidnumber/g" /etc/passwd

fi

#
# Modification de la config backuppc
#

if [ "$bck_user" != "$cur_user" ]; then
	echo "Modification de la config backuppc"
	sed -i "s/USER=$cur_user/USER=$bck_user/g" /etc/init.d/backuppc
	BADLINE=`grep "BackupPCUser}" /etc/backuppc/config.pl | cut -c 2-`
	GOODLINE=`echo $BADLINE |sed -e "s/$cur_user/$bck_user/g" `
	sed -i "s/$BADLINE/$GOODLINE/g" /etc/backuppc/config.pl
	#BADLINE=`grep "CgiAdminUsers}" /etc/backuppc/config.pl | cut -c 2-`
	#GOODLINE=`echo $BADLINE |sed -e "s/$cur_user/$bck_user/g" `
	#sed -i "s/$BADLINE/$GOODLINE/g" /etc/backuppc/config.pl
fi

# Mise en place des droits
/usr/share/se3/sbin/droit_backuppc.sh
