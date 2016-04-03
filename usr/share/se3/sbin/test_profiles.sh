#!/bin/bash
# script test de reparation des profils
# lance en cron
# version 1 par Laurent Joly

m1=0;
m2=0;
delta=0;
>/home/netlogon/delProfile2.txt;
chown -R root:root /home/netlogon/delProfile2.txt;
chmod 755 /home/netlogon/delProfile2.txt;

echo "Debut de test des profils";
for se3_root_username in /home/profiles/*.V2;
do
    [ "$se3_root_username" = '/home/profiles/*.V2' ] && continue;
    se3_username="${se3_root_username##*/}";
    se3_username="${se3_username%.V2}";
    if [ -f "$se3_root_username/NTUSER.DAT" ] && [ -f "$se3_root_username/ntuser.ini" ];
    then 
        m1=$(stat -c %Y $se3_root_username/NTUSER.DAT);
        m2=$(stat -c %Y $se3_root_username/ntuser.ini);
        delta=$(echo $((m1-m2)) | tr -d '-');

        if [ "$delta" -gt "60" ];
        then
            echo $se3_username>>/home/netlogon/delProfile2.txt;
            echo "$se3_username : Profil corrompu";
        fi
        else
        m1=0;
        m2=0;
        if [ -f "$se3_root_username/NTUSER.DAT" ];
        then
            echo $se3_username>>/home/netlogon/delProfile2.txt;
            echo "$se3_username : Profil corrompu (1)";
        fi
        if [ -f "$se3_root_username/ntuser.ini" ];
        then
            echo $se3_username>>/home/netlogon/delProfile2.txt;
            echo "$se3_username : Profil corrompu (2)";
        fi
    fi
done
cat /home/netlogon/delProfile2.txt>>/home/netlogon/delProfile.txt;
rm /home/netlogon/delProfile2.txt;
echo "Fin de tests des profils";
echo "Regeneration programmee";
