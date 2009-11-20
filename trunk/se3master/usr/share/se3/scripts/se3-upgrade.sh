#!/bin/bash
apt-get update >/dev/null 2>&1
(
dpkg -l|grep se3|cut -d ' ' -f3|while read package
do
LC_ALL=C apt-get -s install $package|grep newest >/dev/null|| echo $package
done
)>/tmp/se3_update_list

echo "<br/>"
LC_ALL=C apt-get install $(cat /tmp/se3_update_list) --allow-unauthenticated -y -o Dpkg::Options::=--force-confold >/tmp/se3_update_mail 2>&1
if [ "$?" == "0" ]
then
	echo "Mise a jour ok!<br/>"
else
	echo "Mise a jour non ok!<br/>"
fi
cat /tmp/se3_update_mail | mail -s "[SE3] Résultat de la mise à jour" root
rm -f /tmp/se3_update_mail
rm -f /tmp/se3_update_list
rm -f /etc/se3/update_available
