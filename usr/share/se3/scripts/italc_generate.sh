#!/bin/bash
# Auteur: Olivier Lacroix
#
## $Id$ ##

#
##### script generant les fichiers necessaires au deploiement d italc par wpkg sur les clients: xml + fichiers install.bat #####
#
# sudoifie : il est lance a chaque modif sur les parcs par l interface

# A FAIRE :
# regler la position des fenetres pour chaque parc (pour une repartition sympa des l ouverture d italc)

if [ -e /var/se3/unattended/install/wpkg/packages.xml ]; then
  # on teste la presence du paquet italc pour se3 (officiel)
  TESTITALC="$(cat /var/se3/unattended/install/wpkg/packages.xml | grep "Italc SE3: surveillance, diffusion..." )"
  if [ "$TESTITALC" == "" ]; then
    echo "Le paquet italc n est pas installe : dans le menu applications windows, choisir ajouter une application, sur le forum : italc"
    exit 0
  fi
else
  echo "Le module wpkg doit etre installe pour pouvoir utiliser ce programme."
  exit 0
fi

if [ $# -ne 0 ]; then
  echo "Ce script doit s executer sans argument."
  exit 1
fi

# securite pour eviter plusieurs exec simultanees
if [ -e /tmp/italcgenerate ];
then
  echo "Script deja en cours d execution"
  exit 1
fi
echo en cours > /tmp/italcgenerate

#initialisation des variables #
# Recuperation des parametres mysql

#. /etc/se3/config_c.cache.sh
#. /etc/se3/config_m.cache.sh
. /etc/se3/config_l.cache.sh


# /usr/share/se3/includes/config.inc.sh -lm

BASEDN="$ldap_base_dn"
ADMINRDN="$adminRdn"
ADMINPW="$adminPw"
PEOPLERDN="$peopleRdn"
GROUPSRDN="$groupsRdn"
RIGHTSRDN="$rightsRdn"


REPITALC=/var/se3/unattended/install/italc_keys
mkdir -p $REPITALC

REPWPKG=/var/se3/unattended/install/packages/italc
GLOBALCONFIG=globalconfig.xml

mkdir -p /var/se3/Progs/ro/italc/
PERSOCONFIG=/var/se3/Progs/ro/italc/personalconfig.xml

# on nettoie l ancienne liste des postes profs et eleves des diverses salles
rm $REPWPKG/postesprofs.txt 1 > /dev/null 2>&1
rm -R $REPWPKG/posteseleves 1 > /dev/null 2>&1

DOMAINSE3="`cat /etc/samba/smb.conf | grep workgroup | cut -d= -f2 | sed 's/ //g'`"
echo "$DOMAINSE3" > $REPWPKG/domaine.txt
NETBIOSSE3="`cat /etc/samba/smb.conf | grep 'netbios name' | cut -d= -f2 | sed 's/ //g'`"

# export IDUNIQ (ne fonctionne pas avec les boucles... variable non globale dans les deux while read A do..)
# j ecris sur le disque :-(
echo 1 > /tmp/IDUNIQ

QUALITE="`cat $REPWPKG/config_italc.txt | grep ^QUALITE | cut -d= -f2 | sed "s/\r//g"`"
UPDATEINTERVAL="`cat $REPWPKG/config_italc.txt | grep ^UPDATEINTERVAL | cut -d= -f2 | sed "s/\r//g"`"
MENUSCACHES="`cat $REPWPKG/config_italc.txt | grep ^MENUSCACHES | cut -d= -f2 | sed "s/\r//g"`"

echo "<?xml version=\"1.0\"?><!DOCTYPE italc-config-file><personalconfig version=\"1.0.9\" >  <head>    <globalsettings opened-tab=\"-1\" demoquality=\"$QUALITE\" icononlymode=\"0\" defaultdomain=\"$DOMAINSE3\" role=\"1\" client-update-interval=\"$UPDATEINTERVAL\" wincfg=\"AAAA/wAAAAD9AAAAAAAABAAAAAJ0AAAABAAAAAQAAAAIAAAACPwAAAABAAAAAgAAAAEAAAAWAG0AYQBpAG4AdABvAG8AbABiAGEAcgEAAAAAAAAEAAAAAAAAAAAA\" notooltips=\"0\" win-height=\"682\" win-x=\"-4\" ismaximized=\"1\" win-y=\"-4\" clientdoubleclickaction=\"60\" win-width=\"1024\" showUserColumn=\"0\" toolbarcfg=\"$MENUSCACHES\" />  </head>  <body>" > $PERSOCONFIG

ldapsearch -xLLL -b $parcsRdn,$BASEDN | grep "dn: cn=" | cut -d, -f1 | cut -d= -f2 | while read B
do 
  IDUNIQ="$(cat /tmp/IDUNIQ)"
  PARC="$B"
  
  # on cherche le poste maitre de la salle
  POSTEPROF="$(ldapsearch -xLLL cn=$PARC -b $parcsRdn,$BASEDN | grep description | cut -f2 -d" ")"
  
  if [ "$POSTEPROF" != "" -a "$POSTEPROF" != "0" ] ; then
    echo "$POSTEPROF" >> $REPWPKG/postesprofs.txt

    echo 1 > /tmp/XPOS
    echo 1 > /tmp/YPOS
    
    # on genere les cles publiques et privees des postes profs
    # regle d or du fichier install.bat: on conservera les clefs privees generees dans %Z%\packages\italc\postesprofs\%computername%
    mkdir -p $REPITALC/postesprofs/$POSTEPROF/private/teacher
    mkdir -p $REPITALC/postesprofs/$POSTEPROF/public/teacher
    mkdir -p $REPWPKG/posteseleves
    
    # on genere le debut du globalconfig.xml
    echo "<?xml version=\"1.0\"?> <!DOCTYPE italc-config-file> <globalclientconfig version=\"1.0.9\" > <body>" > $REPITALC/postesprofs/$POSTEPROF/$GLOBALCONFIG
    
    echo "<classroom name=\"$PARC\" >" >> $REPITALC/postesprofs/$POSTEPROF/$GLOBALCONFIG
    echo "<classroom name=\"$PARC\" >" >> $PERSOCONFIG
    
    # la fonction sed "s/\([^0-9]\)\([0-9]*$\)/\\1\t\\2/"|sort +1 -n|tr -d "\t" permet de classer les postes par ordre de num�ro : n17p1 n17p2 n17p10
    ldapsearch -xLLL cn=$PARC | grep $computersRdn | grep member | cut -f1 -d, | cut -f2 -d= | sed "s/\([^0-9]\)\([0-9]*$\)/\\1\t\\2/"|sort -k 1 -n|tr -d "\t" | while read A
    do
      IDUNIQ="$(cat /tmp/IDUNIQ)"
      XPOS="$(cat /tmp/XPOS)"
      YPOS="$(cat /tmp/YPOS)"
      POSTESPARC="$A"
      MACADD="$(ldapsearch -xLLL -b cn=$POSTESPARC,$computersRdn,$BASEDN | grep macAddress | cut -d" " -f2)"
      echo "$POSTEPROF" > $REPWPKG/posteseleves/$POSTESPARC.txt
      if [ "$POSTESPARC" != "$POSTEPROF" ]; then

        if [ "$MACADD" != "" -a "$POSTESPARC" != "" ]; then
          # on rajoute l entree du poste car on a tout
          echo "<client hostname=\"$POSTESPARC:5950\" mac=\"$MACADD\" type=\"0\" id=\"$IDUNIQ\" name=\"$POSTESPARC\" />" >> $REPITALC/postesprofs/$POSTEPROF/$GLOBALCONFIG
          echo "<client w=\"416\" x=\"$XPOS\" y=\"$YPOS\" h=\"312\" visible=\"yes\" id=\"$IDUNIQ\" />" >> $PERSOCONFIG
          
          echo $(($XPOS + 23)) > /tmp/XPOS
          echo $(($YPOS + 23)) > /tmp/YPOS
          
        #~ else
          #~ echo "Il manque un element pour $POSTESPARC : son ip ou son adresse mac"
        fi
        echo $(($IDUNIQ + 1)) > /tmp/IDUNIQ
      #~ else
        #~ echo "Le poste $POSTEPROF n est pas rajoute au xml : inutile de visualiser son propre ecran !"
      fi
    done
    echo "</classroom>" >> $REPITALC/postesprofs/$POSTEPROF/$GLOBALCONFIG
    echo "</classroom>" >> $PERSOCONFIG
    # on finalise les xml
    echo "</body></globalclientconfig>" >> $REPITALC/postesprofs/$POSTEPROF/$GLOBALCONFIG
    
  #~ else
    #~ echo "Pas de poste prof pour le parc $PARC"
  fi
done

echo "</body></personalconfig>" >> $PERSOCONFIG
rm /tmp/IDUNIQ /tmp/XPOS /tmp/YPOS

# compatibilité permse3 : www-se3 est proprio de la branche unattended
# chown -R adminse3 $REPITALC
getent passwd adminse3 >/dev/null && setfacl -R -m u:adminse3:rwx -m d:u:adminse3:rwx $REPITALC


# on est oblige de copier le fichier personnalconfig.xml dans le script de login car la cle permettant de pointer sur un fichier personnalconfig.xml local ne fonctionne pas sur italc 1.0.9!
if [ ! -e /home/templates/profs ];
then
  mkdir -p /home/templates/profs
fi
if [ ! -e /home/templates/profs/logon.bat ];
then
  echo -e "rem Script de login des profs\r" > /home/templates/profs/logon.bat
fi
if [ "`cat /home/templates/profs/logon.bat | grep "personalconfig.xml"`" == "" ]; then
  echo -e "@rem ajout pour italc\r" >> /home/templates/profs/logon.bat
  echo -e "@if not exist %appdata%\\italc mkdir %appdata%\\italc\r" >> /home/templates/profs/logon.bat
  echo -e "@copy /y \\\\\\\\$NETBIOSSE3\\Progs\\\ro\\italc\\personalconfig.xml %appdata%\\italc >NUL\r" >> /home/templates/profs/logon.bat
fi
if [ ! -e /home/templates/administratifs ];
then
  mkdir -p /home/templates/administratifs
fi
if [ ! -e /home/templates/administratifs/logon.bat ];
then
  echo -e "rem Script de login des administratifs\r" > /home/templates/administratifs/logon.bat
fi
if [ "`cat /home/templates/administratifs/logon.bat | grep "personalconfig.xml"`" == "" ]; then
  echo -e "@rem ajout pour italc\r" >> /home/templates/administratifs/logon.bat
  echo -e "@if not exist %appdata%\\italc mkdir %appdata%\\italc\r" >> /home/templates/administratifs/logon.bat
  echo -e "@copy /y \\\\\\\\$NETBIOSSE3\\Progs\\\ro\\italc\\personalconfig.xml %appdata%\\italc >NUL\r" >> /home/templates/administratifs/logon.bat
fi

if [ -e /tmp/italcgenerate ];
then
  rm /tmp/italcgenerate
fi

