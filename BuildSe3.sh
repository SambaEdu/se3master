#!/bin/bash

echo "deprecated à revoir pour git"
exit 0


# Patchage des numeros de versions

VERSION="A recup dans fichier control"
MAJNB=160
while true; do
	if [ ! -e var/cache/se3_install/maj/maj$MAJNB.sh ]; then
		break
	fi
	let MAJNB+=1
done

# fichier control à gérer 
#mv DEBIAN/control.$DISTRIB ctrl

# ya surement plus propre à faire que ça !
mv var/cache/se3_install/se3db.sql se3db.sql
cat se3db.sql | sed -e "s/#VERSION#/$VERSION/g" | sed -e "s/#MAJNBR#/$MAJNB/g" > var/cache/se3_install/se3db.sql
rm  se3db.sql 

echo "Version $VERSION du "`date` > var/cache/se3_install/version

#important à conserver
# Remise en place des droits sur les fichiers


chmod -R 755 DEBIAN
chmod -R 750 var/cache/se3_install
chmod 644 var/cache/se3_install/conf/*
chmod 644 var/cache/se3_install/reg/*
chmod 755 var/cache/se3_install/conf/apachese
chmod 600 var/cache/se3_install/conf/config.inc.php.in
chmod 600 var/cache/se3_install/conf/SeConfig.ph.in
chmod 600 var/cache/se3_install/conf/slapd_*.in
chmod 640 var/cache/se3_install/conf/mrtg.cfg
chmod 440 var/cache/se3_install/conf/sudoers

# Changement des fins de lignes dos
#important à conserver..... A voir si dos2unix existe encore !
dos2unix var/cache/se3_install/scripts/*.sh
dos2unix var/cache/se3_install/scripts/*.pl
dos2unix var/cache/se3_install/sudoscripts/*.sh
dos2unix var/cache/se3_install/sudoscripts/*.pl


# Fabrication du paquet se3
