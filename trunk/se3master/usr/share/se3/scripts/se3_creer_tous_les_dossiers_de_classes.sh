#!/bin/bash

##### script permettant de creer/rafraichir les dossiers de classes #####
# Stephane Boireau 29/12/2014


#$Id$


acl_group_profs_classes=$(/usr/bin/getfacl /var/se3/Classes | grep default:group:Profs)

ldapsearch -xLLL cn=Classe_*|grep "^cn: Classe_"|sed -e "s|^cn: Classe_||"|while read classe
do
	/usr/share/se3/scripts/updateClasses.pl -c $classe
done

if [ -n "$acl_group_profs_classes" ]; then
	/usr/share/se3/scripts/se3_droits_profs_sur_classes.sh
fi

