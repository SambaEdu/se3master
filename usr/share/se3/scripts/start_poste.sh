#!/bin/sh
# SambaEdu
#
# $Id$
#

WWWPATH="/var/www"

if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible"
	exit 1
fi
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
	echo "Impossible d'accéder au paramètre BASEDN"
	exit 1
fi

COMPUTERSRDN=`echo "SELECT value FROM params WHERE name='ComputersRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$COMPUTERSRDN" ]; then
	echo "Impossible d'accéder au paramètre COMPUTERSRDN"
	exit 1
fi

PARCSRDN=`echo "SELECT value FROM params WHERE name='parcsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PARCSRDN" ]; then
	echo "Impossible d'accéder au paramètre PARCSDN"
	exit 1
fi
PASSADM=`echo "SELECT value FROM params WHERE name='xppass'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PASSADM" ]; then
	echo "Impossible d'accéder au paramètre PASSADM"
	exit 1
fi

if [ -z "$2" ]
then
	echo "Ce script est destine a provoquer l'allumage,"
	echo "l'extinction ou le reboot d'une machine."
	echo "USAGE: Passer en parametres le nom netbios de la machine et l'action."
	echo "       L'action doit etre shutdown, reboot ou wol."
	echo "       Exemple: $0 CDI01 reboot"
#	echo "Les parcs existants sont :"
#	ldapsearch  -x -b $PARCSRDN,$BASEDN '(objectclass=*)'  | grep cn |  grep -v requesting | grep -i -v Rights | grep -i -v member
else
	ldapsearch  -xLLL -b cn=$1,$COMPUTERSRDN,$BASEDN '(objectclass=*)' macAddress | grep macAddress | while read C
	do
		echo "$C" | cut -d: -f 2-7  | while read D
		do
			getent passwd $1$>/dev/null && TYPE="XP"
			if [ "$TYPE" = "XP" ]; then
				echo "<br><h2>Action sur : $1</h2><br>"
				if [ "$2" = "shutdown" ]; then
					echo "<h3>Tentative d'arret de la machine $1</h3><br>"
					/usr/bin/net rpc shutdown -t 2 -f -C "Arret demande par le serveur sambaEdu3" -S $1 -U "$1\adminse3%$PASSADM"
				fi
				if [ "$2" = "reboot" ]; then
					echo "<h3>Tentative de reboot de la machine $1</h3><br>"
					/usr/bin/net rpc shutdown -t 2 -r -f -C "Arret demande par le serveur sambaEdu3" -S $1 -U "$1\adminse3%$PASSADM"
				fi
				if [ "$2" = "wol" ]; then
					echo "Tentative d'eveil pour la machine correspondant a l'adresse mac $D<br>"
				        ldapsearch  -xLLL -b cn=$1,$COMPUTERSRDN,$BASEDN '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g;s/\.[0-9]*$/.255/g" | while read I
	                                do
					       echo "Broadcast: $I<br>"
					       /usr/bin/wakeonlan  -i $I $D > /dev/null
					       /usr/bin/wakeonlan   $D > /dev/null
					done
				fi
			else
				# On teste si on a un windows ou un linux
				ldapsearch  -x -b uid=$B$,$COMPUTERSRDN,$BASEDN '(objectclass=*)' uidNumber | grep uid |grep -v requesting | grep -v base
				# On peut penser que l'on a un linux, mais cela peut aussi être un win 9X
				# A affiner
				if [ $? = "1" ]
				then
				if [ "$2" = "wol" ]; then
					echo "Tentative d'eveil pour la machine correspondant a l'adresse mac $D<br>"
				        ldapsearch  -xLLL -b cn=$1,$COMPUTERSRDN,$BASEDN '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g;s/\.[0-9]*$/.255/g" | while read I
	                                do
					       echo "broadcast: $I<br>"
					       /usr/bin/wakeonlan  -i $I $D > /dev/null
					       /usr/bin/wakeonlan   $D > /dev/null
					done
				fi

					if [ "$2" = "shutdown" ]; then
						echo "<h3>Tentative d'arret de la machine $1</h3><br>"
						/usr/bin/ssh -o StrictHostKeyChecking=no $1 halt
					fi
				fi
			fi
		done
	done
fi
