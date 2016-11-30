#!/bin/bash
# script d'effacement des profiles
# 
# a lancer en cron sans argument ou bien avec all par l'interface
#
temoin="/home/netlogon/delProfile.txt"
actifs=$(smbstatus -b | awk '{ print $2}' | sort -u)
if [ ! -e "$temoin" ];then
	touch $temoin
fi 

fromdos $temoin

if [ "$1" = "all" ];then

	for dossier in $(ls /home/profiles/ 2>/dev/null)
		do
			user=$(echo "$dossier" | cut -d "." -f1)
			if [ -z "$(grep "$user" $temoin)" ]; then
				echo "$user" >> $temoin
				echo "Suppression profil Utilisateur $user <br/>"
			fi
		done
fi

while read nom ; do
    if [ -n "$nom" ] ; then
        if $(echo $actifs | grep -q $nom) ; then
            for pid in $(smbstatus -p -b -u $nom | grep "$nom" | awk '{print $1}') ; do
                kill $pid
                echo "pid $pid tue"
            done
	   sleep 5
        fi
        rm -fr /home/profiles/$nom > /dev/null 2>&1
        rm -fr /home/profiles/$nom.V* > /dev/null 2>&1
#       sed -i  "/~$nom$/d" /home/netlogon/delProfile.txt 
        echo  "$nom supprimé"
    fi
done < $temoin


echo > $temoin
setfacl -m  u:www-se3:rwx $temoin
exit 0

