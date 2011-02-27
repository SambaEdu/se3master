#!/bin/bash

for user in $(ls /home/profiles/ 2>/dev/null)
do
    smbstatus -b|grep $user >/dev/null
    if [ "$?" == "0" ]; then
        >/home/$user/profil/delHive
        echo "Utilisateur $user connect&#233;, programmation suppression profil<br/>"
    else
        echo "Suppression du profil Windows de $user<br/>"
        rm -fr /home/profiles/$user
    fi
done
