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

chown -R $bck_user.www-data /etc/backuppc
chmod -R 770 /etc/backuppc
chown $bck_user.www-data /etc/SeConfig.ph
chmod 640 /etc/SeConfig.ph
chown $bck_user /usr/share/backuppc/cgi-bin/index.cgi
chmod u+s /usr/share/backuppc/cgi-bin/index.cgi
chown -R $bck_user /var/run/backuppc
getfacl /var/lib/backuppc 2>/dev/null|grep owner|grep $bck_user||chown -R $bck_user /var/lib/backuppc
