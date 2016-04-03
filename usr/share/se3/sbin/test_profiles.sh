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
for line in /home/profiles/*.V2; do
line2=${line:15:$((${#line}-18))};
if [ -f "$line/NTUSER.DAT" ] && [ -f "$line/ntuser.ini" ];
then 
    m1=$(stat -c %Y $line/NTUSER.DAT);
    m2=$(stat -c %Y $line/ntuser.ini);
    delta=$(echo $((m1-m2)) | tr -d '-');

    if [ "$delta" -gt "60" ];
    then
      echo $line2>>/home/netlogon/delProfile2.txt;
      echo "$line2 : Profil corrompu";
    fi
else
    m1=0;
    m2=0;
    if [ -f "$line/NTUSER.DAT" ];
    then
        echo $line2>>/home/netlogon/delProfile2.txt;
        echo "$line2 : Profil corrompu (1)";
    fi
    if [ -f "$line/ntuser.ini" ];
    then
        echo $line2>>/home/netlogon/delProfile2.txt;
        echo "$line2 : Profil corrompu (2)";
    fi
fi
done
cat /home/netlogon/delProfile2.txt>>/home/netlogon/delProfile.txt;
rm /home/netlogon/delProfile2.txt;
echo "Fin de tests des profils";
echo "Regeneration programmee";
