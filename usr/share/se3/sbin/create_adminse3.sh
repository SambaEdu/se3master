#!/bin/bash

# Recup param mysql
dbhost=$(expr "$(grep mysqlServerIp /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbuser=$(expr "$(grep mysqlServerUsername /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbpass=$(expr "$(grep mysqlServerPw /etc/SeConfig.ph)" : ".*'\(.*\)'.*")
dbname=$(expr "$(grep connexionDb /etc/SeConfig.ph)" : ".*'\(.*\)'.*")

# Compte administrateur local des postes
ADMINSE3=`gawk -F'=' '/compte_admin_local/ {gsub("\r","");print $2}' /var/se3/Progs/install/installdll/confse3.ini`
PASSADMINSE3=`echo "SELECT value FROM params WHERE name='xppass'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

if [ "$ADMINSE3" == "" ]; then
   echo "Pas de compte adminse3 (compte_admin_local) défini dans /var/se3/Progs/install/installdll/confse3.ini (L:\\install\\installdll\\confse3.ini)."
   exit 1
fi   
if [ "$PASSADMINSE3" == "" ]; then
   echo "Pas de mot de passe défini pour $ADMINSE3 : le champ 'xppass' de la table params de la base mysql se3db est vide ou absent."
   exit 1
fi   
if ( getent passwd $ADMINSE3 > /dev/null ) ; then
   echo "Le compte $ADMINSE3 existe déjà : son mot de passe est mis à jour."
   /usr/share/se3/sbin/userChangePwd.pl $ADMINSE3 $PASSADMINSE3
else
   # Creation user adminse3
   UIDPOLICY=`echo "SELECT value FROM params WHERE name='uidPolicy'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
   if [ "$UIDPOLICY" != "4" ]; then
      echo "UPDATE params SET value='4' WHERE name='uidPolicy'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass
   fi
   # adminse3 c'est un mâle ;-)
   if ( ! /usr/share/se3/sbin/userAdd.pl 3 adminse $PASSADMINSE3 00000000 M Administratifs ) ; then
      echo "Erreur de création du compte $ADMINSE3"
   fi
   if [ "$UIDPOLICY" != "4" ]; then
      echo "UPDATE params SET value=\"$UIDPOLICY\" WHERE name='uidPolicy'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
   fi
   echo "Le compte $ADMINSE3 a été ajouté dans l'annuaire."
fi
