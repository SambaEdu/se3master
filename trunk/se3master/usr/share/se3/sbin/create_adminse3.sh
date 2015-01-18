#!/bin/bash
. /usr/share/se3/includes/config.inc.sh -cm
. /usr/share/se3/includes/functions.inc.sh

# Recup param mysql
# dbhost=$(expr "$(grep mysqlServerIp /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
# dbuser=$(expr "$(grep mysqlServerUsername /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
# dbpass=$(expr "$(grep mysqlServerPw /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
# dbname=$(expr "$(grep connexionDb /etc/SeConfig.ph)" : ".*'\(.*\)'.*")

# Compte administrateur local des postes
ADMINSE3="adminse3"

if [ "$xppass" == "" ]; then
   echo "Pas de mot de passe d�fini pour $ADMINSE3 : le champ 'xppass' de la table params de la base mysql se3db est vide ou absent."
   exit 1
fi   
if ( getent passwd $ADMINSE3 > /dev/null ) ; then
   echo "Le compte $ADMINSE3 existe d�j� : son mot de passe est mis � jour."
   /usr/share/se3/sbin/userChangePwd.pl $ADMINSE3 $xppass
else
   # Creation user adminse3
   if [ "$uidPolicy" != "4" ]; then
     CHANGEMYSQL uidPolicy 4 
   fi
   # adminse3 c'est un m�le ;-)
   if ( ! /usr/share/se3/sbin/userAdd.pl 3 adminse $xppass 00000000 M Administratifs ) ; then
      echo "Erreur de cr�ation du compte $ADMINSE3"
   fi
   if [ "$uidPolicy" != "4" ]; then
      CHANGEMYSQL uidPolicy "$uidPolicy"
      # echo "UPDATE params SET value=\"$UIDPOLICY\" WHERE name='uidPolicy'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
   fi
   echo "Le compte $ADMINSE3 a �t� ajout� dans l'annuaire."
fi
smbpasswd -e root
echo -e "$xppass\n$xppass"|(/usr/bin/smbpasswd -s root)
net -U root%"$xppass" rpc rights grant admin SeMachineAccountPrivilege SePrintOperatorPrivilege
net -U root%"$xppass" rpc rights grant adminse3 SeMachineAccountPrivilege SePrintOperatorPrivilege
smbpasswd -d root
passtmp=$(makepasswd)
echo -e "$passtmp\n$passtmp"|(/usr/bin/smbpasswd -s root) 


echo "Attention, la mise au domaine se fait maintenant avac le compte adminse3.
Le compte root samba est maintenant desactive"

