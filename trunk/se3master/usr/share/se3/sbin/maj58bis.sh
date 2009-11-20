#!/bin/bash

cd /var/cache/se3_install

# Ajout du pass root mysql dans .my.cnf si besoin
if [ ! -e /root/.my.cnf ]; then
	# On démarre mysql en safe_mode
	# On initialise le pass mysql et génere le .my.cnf
	MYSQLPW=`date | md5sum | cut -c 1-6`
	/etc/init.d/mysql stop
	sleep 5
	mysqld --version | grep "4.1." && MYVER="4.1"
	if [ ! "$MYVER" = "4.1" ]; then
		/usr/sbin/mysqld --skip-grant-tables & # --skip-networking
		sleep 5
		mysql -D mysql -e "UPDATE user SET password=PASSWORD('$MYSQLPW') WHERE user='root'"
	else
		/usr/bin/mysqld_safe --skip-grant-tables & # --skip-networking
		mysqladmin password $MYSQLPW
	fi
	sleep 5
	echo "[client]">/root/.my.cnf
	echo "password=$MYSQLPW">>/root/.my.cnf
	echo "user=root">>/root/.my.cnf
	# On redémarre mysql en mode normal
	/etc/init.d/mysql stop
	if [ -e /usr/bin/safe_mysqld ]; then
		killall -9 safe_mysqld 2&> /dev/null
	else
		killall -9 mysqld_safe 2&> /dev/null
	fi
	killall -9 mysqld 2&> /dev/null
	/etc/init.d/mysql start
fi

# Création de la base inventaire et des comptes d'acces
mysqladmin drop Inventory -f 2&> /dev/null
mysqladmin create Inventory
PASSOCS=`date|md5sum|cut -c3-9`
ADMINPW="wawa"
mysql -D mysql -e  "DELETE FROM user WHERE User = 'ocsro'"
mysql -D mysql -e  "DELETE FROM user WHERE User = 'ocsadmin'"
mysql -D mysql -e  "DELETE FROM db WHERE User = 'ocsro'"
mysql -D mysql -e  "DELETE FROM db WHERE User = 'ocsadmin'"
# On crée le user ocsadmin de la table mysql.db , mysql.user
mysql -D mysql -e "INSERT INTO user (Host,User,Password,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Reload_priv,Shutdown_priv,Process_priv,File_priv,Grant_priv,References_priv,Index_priv,Alter_priv) VALUES ('localhost','ocsadmin',PASSWORD('$PASSOCS'),'N','N','N','N','N','N','N','N','N','N','N','N','N','N')" 
mysql -D mysql -e  "INSERT INTO db (Host,Db,User,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Grant_priv,References_priv,Index_priv,Alter_priv) VALUES ('localhost','Inventory','ocsadmin','Y','Y','Y','Y','Y','N','N','N','N','N')" 

# On crée le user ocsro de la table mysql.db , mysql.user et mysql.table_priv avec droit select et mdp admin LDAP
mysql -D mysql -e "INSERT INTO user (Host,User,Password,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Reload_priv,Shutdown_priv,Process_priv,File_priv,Grant_priv,References_priv,Index_priv,Alter_priv) VALUES ('localhost','ocsro',PASSWORD('$ADMINPW'),'N','N','N','N','N','N','N','N','N','N','N','N','N','N')" 
mysql -D mysql -e  "INSERT INTO db (Host,Db,User,Select_priv,Insert_priv,Update_priv,Delete_priv,Create_priv,Drop_priv,Grant_priv,References_priv,Index_priv,Alter_priv) VALUES ('localhost','Inventory','ocsro','N','N','N','N','N','N','N','N','N','N')" 
mysql -D mysql -e "DELETE FROM  tables_priv where Host = 'localhost' AND Db= 'Inventory' AND User = 'ocsro' " 
for TBL in BIOS CONTROLLERS DRIVES HARDWARE INPUTS MEMORIES MODEMS MONITORS NETWORKS PORTS PRINTERS SLOTS SOUNDS STORAGES VIDEOS
do
	mysql -D mysql -e "INSERT INTO tables_priv VALUES ('localhost', 'Inventory', 'ocsro', '$TBL', '', 20050331011228, 'Select', '')" 2&>/dev/null
done
mysqladmin reload

# Patchage du fichier de conf inventaire
cat conf/conf_invent.inc.php.in | sed -e "s/#OCSADMPASS#/$PASSOCS/g" | sed -e "s/#OCSROPASS#/$ADMINPW/g" > /var/www/se3/inventaire/conf_invent.inc.php
chown www-se3 /var/www/se3/inventaire/conf_invent.inc.php
chmod 400 /var/www/se3/inventaire/conf_invent.inc.php

# Remplissage de la base Inventory
mysql Inventory < ocs/Inventory.sql

