#!/bin/bash
# script test de reparation des profils
# lance en cron
# version 4 par Laurent Joly

m1=0;
m2=0;
delta=0;
>/home/netlogon/delProfile2.txt;
chown -R root:root /home/netlogon/delProfile2.txt;
chmod 755 /home/netlogon/delProfile2.txt;

#echo "Debut de test des profils";
cd /
for se3_root_username in home/profiles/*.V2;
do
    [ "$se3_root_username" = '/home/profiles/*.V2' ] && continue;
    se3_username="${se3_root_username##*/}";
    se3_username="${se3_username%.V2}";
	
#echo "Verification des droits";
if [ -z $(getfacl $se3_root_username | grep "^other::rwx") ];
then
    setfacl -b $se3_root_username;
    setfacl -Rm user::rwx $se3_root_username;
    setfacl -Rm group::rwx $se3_root_username;
    setfacl -Rm other::rwx $se3_root_username;
    echo "$se3_username : ACL corrompu par W7";
# else
#   echo "Pour une fois W7 ne pete rien";
fi

#echo "verification du ntuser.dat";
    m1=0;
    m2=0;
    delta=0;
    if [ -f "$se3_root_username/NTUSER.DAT" ];
    then
        m1=$(stat -c %Y $se3_root_username/NTUSER.DAT);
    fi
    if [ -f "$se3_root_username/ntuser.dat" ];
    then
        m1=$(stat -c %Y $se3_root_username/ntuser.dat);
    fi
    if [ -f "$se3_root_username/ntuser.ini" ];
    then
        m2=$(stat -c %Y $se3_root_username/ntuser.ini);
    fi

    delta=$(echo $((m1-m2)) | tr -d '-');

    if [ "$delta" -gt "600" ];
    then
        echo $se3_username>>/home/netlogon/delProfile2.txt;
        echo "$se3_username : Profil corrompu ($delta s)";
    fi
done
cat /home/netlogon/delProfile2.txt>>/home/netlogon/delProfile.txt;
rm /home/netlogon/delProfile2.txt;
#echo "Fin de tests des profils";
#echo "Regeneration programmee";
