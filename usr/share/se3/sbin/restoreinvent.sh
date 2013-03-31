#!/bin/bash




#
# Olivier Lécluse
#
# distribué sous licence GPL
#
##### Permet de recréer l'inventaire en cas d'effacement accidentel ou en cas de problème... ##### 
##$Id$##
#

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Permet de recréer l'inventaire en cas d'effacement accidentel ou en cas de problème... " 
    echo "Usage : aucune option"
	exit
fi	

# Création de la base inventaire et des comptes d'acces
mysqladmin drop Inventory -f 2&> /dev/null
mysqladmin create Inventory
PASSOCS="5289992"
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

# On crée le user ocsro de la table mysql.db , mysql.user et mysql.table_priv avec droit select et mdp admin LDAP
for TBL in BIOS CONTROLLERS DRIVES HARDWARE INPUTS MEMORIES MODEMS MONITORS NETWORKS PORTS PRINTERS SLOTS SOUNDS STORAGES VIDEOS
do
	mysql -D mysql -e "INSERT INTO tables_priv VALUES ('localhost', 'Inventory', 'ocsro', '$TBL', '', 20050331011228, 'Select', '')" 2&>/dev/null
done
mysqladmin reload

# Remplissage de la base Inventory
mysql Inventory < /var/cache/se3_install/ocs/Inventory.sql

# Installation des scripts OCS
rm -r /var/se3/Progs/rw/inventaire
if [ ! -d /var/se3/Progs/rw/inventaire ]; then
    mkdir -p /var/se3/Progs/rw/inventaire
fi
tar -zxf /var/cache/se3_install/ocs/APPocs.tar.gz -C /var/se3/Progs/rw/inventaire
cp /var/cache/se3_install/ocs/Config.csv /var/se3/Progs/rw/inventaire/Application
cp /var/cache/se3_install/ocs/startocs.vbs /var/se3/Progs/ro
chmod 755 /var/se3/Progs/ro/startocs.vbs
chmod 666 /var/se3/Progs/rw/inventaire/Application/Config.csv
# Patchage du fichier de conf inventaire
cat /var/cache/se3_install/conf/conf_invent.inc.php.in | sed -e "s/#OCSADMPASS#/$PASSOCS/g" | sed -e "s/#OCSROPASS#/$ADMINPW/g" > /var/www/se3/inventaire/conf_invent.inc.php
chown www-se3 /var/www/se3/inventaire/conf_invent.inc.php
chmod 400 /var/www/se3/inventaire/conf_invent.inc.php

cp /var/cache/se3_install/ocs/OCSInventory.bmp /var/se3/Progs/rw/inventaire/Application
for EXTENSION in exe dll bmp
do
        setfacl -m m::rx /var/se3/Progs/rw/inventaire/Application/*.$EXTENSION
done
