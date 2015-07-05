#!/bin/bash
# Auteurs: Olivier Lacroix
#
# Test du changement de mot de passe : Stéphane Boireau
#


## $Id$ ##


#
##### script permettant de déléguer smb_web_is_open à ceux qui ont changé leur mot de passe #####

#Remarque:
# exécution "un peu" longue : 10 minutes pour 1100 utilisateurs


#Couleurs
COLTITRE="\033[1;35m"	# Rose
COLPARTIE="\033[1;34m"	# Bleu
COLTXT="\033[0;37m"	# Gris
COLCHOIX="\033[1;33m"	# Jaune
COLDEFAUT="\033[0;33m"	# Brun-jaune
COLSAISIE="\033[1;32m"	# Vert
COLCMD="\033[1;37m"	# Blanc
COLERREUR="\033[1;31m"	# Rouge
COLINFO="\033[0;36m"	# Cyan

ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$0 n'a pas besoin d'argument pour fonctionner"
	echo -e "$COLINFO\c"
        echo "Exemples :"
	echo -e "$COLTXT"
        echo "smbweb_open_for_passwd_changed.sh donne le droit smbweb_is_open à tous les utilisateurs ayant changé leur mot de passe." 
        echo
	exit 1
}

#teste si 0 argument
if [ $# -ne 0  ] ; then
  ERREUR
  exit 1
fi

WWWPATH="/var/www"
## recuperation des variables necessaires pour interroger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
  dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f2 | cut -d \" -f2`
  dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
  dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
  dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
  echo -e "$COLERREUR"
  echo "Fichier de configuration mysql inaccessible, le script ne peut se poursuivre."
  exit 1
fi

#~ ### recuperation des parametres actuels de l'annuaire dans la base ####
BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
PEOPLERDN=`echo "SELECT value FROM params WHERE name=\"peopleRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
RIGHTSRDN=`echo "SELECT value FROM params WHERE name=\"rightsRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

# debut du script proprement dit

# recupération de l'option dans mysql (1 si ce script est actif)
OPTION=`echo "select value from params where name='smbwebopen_pwd_chg'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

### on remplit overfill pour les partitions sur lesquelles c'est paramétré ####
if [ "$OPTION" != "1" ]; then
  echo "Le droit smb_web_is_open n est pas lie au changement de mot de passe : a configurer dans Annuaire, Tester les mots de passe... Aucune modification effectuée."
  exit
else
  # on liste les utilisateurs ayant le droit smbweb_is_open
  USER_SMBOPEN="`ldapsearch -xLLL cn=smbweb_is_open | grep member | cut -d"," -f1 | cut -d"=" -f2`"
  
    # on liste tous les utilisateurs, et on examine si leur mot de passe est changé
  ldapsearch -xLLL uid | grep "$PEOPLERDN" | grep "uid=" | cut -d"," -f1 | cut -d"=" -f2 | while read A
    do       
            uid=$(echo "$A" | cut -d" " -f2)
            date=$(ldapsearch -xLLL uid=$uid | grep "^gecos:" | cut -d"," -f2)
            if [ "$date" == "" ]; then
              #certains utilisateurs n'ont pas de date de naissance : admin, ...
              echo "La date de naissance de $uid n'est pas dans l'annuaire, on ne touche pas au droit smbweb_is_open déjà en place"
            else
              TEST="$(echo "$USER_SMBOPEN" | grep "^$uid$")" # appartenance de $uid aux membres du droit smbweb_is_open..
              if smbclient -L 127.0.0.1 -U $uid%$date > /dev/null ; then
                if [ "$TEST" == "" ]; then
                  echo "L'utilisateur $uid n'a pas le droit smbweb_is_open, il n'a pas changé son mdp. Tout va bien."
                else
                  echo -e "$COLERREUR"
                  echo "L'utilisateur $uid a le droit smb_web_is_open et n'a pas changé son mdp: on lui enlève !!!"
                  echo -e "$COLTXT"
                  
                  RDn="cn=smbweb_is_open,$RIGHTSRDN,$BASEDN"
                  PersDn="uid=$uid,$PEOPLERDN,$BASEDN"
                  /usr/bin/perl /usr/share/se3/sbin/groupDelEntry.pl "$PersDn" "$RDn"
                fi
              else
                if [ "$TEST" == "" ]; then
                  echo -e "$COLERREUR"
                  echo "L'utilisateur $uid n'a pas le droit smbweb_is_open, il a changé son mdp: on le rajoute dans les membres de smbweb_is_open !!!"
                  echo -e "$COLTXT"
                  
                  RDn="cn=smbweb_is_open,$RIGHTSRDN,$BASEDN"
                  PersDn="uid=$uid,$PEOPLERDN,$BASEDN"
                  /usr/bin/perl /usr/share/se3/sbin/groupAddEntry.pl "$PersDn" "$RDn"
                else
                  echo "L'utilisateur $uid a le droit smbweb_is_open et a changé son mdp. Tout va bien."
                fi
              fi
            fi
    done
fi
