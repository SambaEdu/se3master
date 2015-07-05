#!/bin/bash
#

## $Id$ ##

#
##### Permet de faire la mise à jour de se3 et modules #####

# 

if [ "$1" = "--help" -o "$1" = "-h" ]
then
        echo "Script permettant la mise a jour de se3 et de ses modules"
        echo "Usage : sans option "
        exit
fi	

echo "<pre>"
echo "Mise a jour de la liste des paquets disponibles ....."
apt-get -qq update
(
dpkg -l|grep se3|cut -d ' ' -f3|while read package
do
LC_ALL=C apt-get -s install $package|grep newest >/dev/null|| echo $package
done
)>/tmp/se3_update_list

#echo "<br/>"
LC_ALL=C apt-get -s install se3-domain 2>/dev/null | grep 'Inst' && apt-get install se3-domain --allow-unauthenticated -y | tee -a /tmp/se3_update_mail 
LC_ALL=C apt-get install $(cat /tmp/se3_update_list) --allow-unauthenticated -y -o Dpkg::Options::=--force-confold 2>&1 | tee -a /tmp/se3_update_mail 
if [ "$?" == "0" ]
then
	echo "</pre>"
	echo "Mise a jour ok !<br/>"
else
	echo "</pre>"
	echo "Mise a jour non ok !<br/>"
fi

cat /tmp/se3_update_mail | mail -s "[SE3] Resultat de la mise a jour" root
rm -f /tmp/se3_update_mail
rm -f /tmp/se3_update_list
rm -f /etc/se3/update_available
