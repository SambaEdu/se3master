#!/bin/bash

# Patchage des numeros de versions

VERSION=$1
DISTRIB=$2
BRANCHE=$3

if ([ -z "$VERSION" ] || [ -z "$DISTRIB" ] || [ -z "$BRANCHE" ]); then
echo "$0 version, distrib, branche (stable ou xp)"
echo "ex : $0 1.20 lenny xp"
exit 1
fi


[ "$BRANCHE" == "" -o "$BRANCHE" != "stable" ] && OPT="XP"

if [ -z "$DISTRIB" ]; then
	DISTRIB="etch"
	echo "etch choisi par défaut, OK ?"
	read dummy

fi

if [ -d build ]; then
	rm -r build
fi
mkdir build
cp -r se3master/* build
cd build
../svnrmadm
MAJNB=48
while true; do
	if [ ! -e var/cache/se3_install/maj/maj$MAJNB.sh ]; then
		break
	fi
	let MAJNB+=1
done

mv DEBIAN/control.$DISTRIB ctrl
rm DEBIAN/control.*
# mv DEBIAN/postinst.$DISTRIB DEBIAN/pst
# rm DEBIAN/postinst.*
# mv DEBIAN/pst DEBIAN/postinst

cat ctrl | sed -e "s/#VERSION#/$VERSION/g"> DEBIAN/control
mv var/cache/se3_install/se3db.sql se3db.sql
cat se3db.sql | sed -e "s/#VERSION#/$VERSION/g" | sed -e "s/#MAJNBR#/$MAJNB/g" > var/cache/se3_install/se3db.sql
rm  ctrl se3db.sql 

echo "Version $VERSION du "`date` > var/cache/se3_install/version

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

dos2unix var/cache/se3_install/scripts/*.sh
dos2unix var/cache/se3_install/scripts/*.pl
dos2unix var/cache/se3_install/sudoscripts/*.sh
dos2unix var/cache/se3_install/sudoscripts/*.pl

# tratement utf8 pour etch 
#if [ "$DISTRIB" == "etch" ]; then
#	rm var/cache/se3_install/install_se3_lenny.sh

#else
#	rm var/cache/se3_install/install_se3_etch.sh

#fi

echo "Modif vers utf8 pour etch / Lenny"
recode latin9..utf8 var/cache/se3_install/scripts/*.sh
recode latin9..utf8 var/cache/se3_install/sudoscripts/*.sh
recode latin9..utf8 var/cache/se3_install/*.sh



# tratement utf8 pour etch 
cd var/cache/se3_install/wwwse3
# if [ "$DISTRIB" == "etch" ]; then
# A=`find ./ -iname "*.inc" -o -iname "*.php" -o -iname "*.html" -type f`
# 	        for FICH in $A
# 		do
# 			recode latin9..utf8 $FICH
# 			echo "$FICH-->ok"
# 		done
# fi

# Refabrication de l'archive wwwse3.tgz
tar -czf ../wwwse3.tgz se3
cd ..
rm -r wwwse3
cd ../../../..



# Fabrication du paquet se3
dpkg-deb -b build se3_$VERSION$DISTRIB\_i386.deb

if [ "$DISTRIB" == "etch" ]; then
	scp -P 2222 se3_${VERSION}etch_i386.deb root@wawadeb:/var/ftp/debian/dists/etch/se3$OPT/binary-i386/net/
else
	 scp -P 2222 se3_${VERSION}lenny_i386.deb root@wawadeb:/var/ftp/debian/dists/stable/se3$OPT/binary-i386/net/
fi
