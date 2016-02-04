#!/bin/bash
# script d'effacement des profiles
# pas d'arguments
# a lancer en cron 
#

actifs=$(smbstatus -b | awk '{ print $2}' | sort -u)
fromdos /home/netlogon/delProfile.txt

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
        rm -fr /home/profiles/$nom.V2 > /dev/null 2>&1
#       sed -i  "/~$nom$/d" /home/netlogon/delProfile.txt 
        echo  "$nom supprime"
    fi
done < /home/netlogon/delProfile.txt


echo > /home/netlogon/delProfile.txt



