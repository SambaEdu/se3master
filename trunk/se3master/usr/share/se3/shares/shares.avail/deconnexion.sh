#!/bin/bash
#shares_Win95: homes
#shares_Win2K: homes
#shares_WinXP: homes
#shares_Vista: homes
#action: stop
#level: 10

if [ -e /var/www/se3/includes/config.inc.php ]; then
        dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "Fichier de conf inaccessible"
        exit 1
fi

echo "update connexions set logouttime=now() where username='$1' and netbios_name='$2' and logouttime=0;" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N

