#!/bin/bash

hostname -d | grep -i ".clg14." && ETAB="clg14"
hostname -d | grep -i ".clg50." && ETAB="clg50"
hostname -d | grep -i ".clg61." && ETAB="clg61"
hostname -d | grep -i ".lyc14." && ETAB="lyc14"
hostname -d | grep -i ".lyc50." && ETAB="lyc50"
hostname -d | grep -i ".lyc61." && ETAB="lyc61"

if [ ! -z "$ETAB" ]; then
	echo "Installation des mises a jour departementales pour $ETAB"
	paraminst=`echo "SELECT count(*) FROM params WHERE name='majdepnbr';" | mysql se3db -N`
	if [ "$paraminst" = "0" ]; then
		mysql -D se3db -e "INSERT INTO params VALUES ('','majdepnbr',0,0,'indice de maj departementale',4);"
	fi
	cat maj_se.sh | sed -e "s/#ETAB#/$ETAB/g"> /usr/sbin/maj_se.sh
	chmod 700 /usr/sbin/maj_se.sh
	echo "0 6 * * 1-5 root /usr/sbin/maj_se.sh">>/etc/crontab
	/usr/sbin/maj_se.sh
fi
