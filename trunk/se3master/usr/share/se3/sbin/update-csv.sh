#!/bin/bash
# Auteur: Denis Bonnenfant
#
#
## $Id$ ##
#
##### script generant le fichier unattend.csv à partir du ldap #####

TEMOIN=/tmp/csvtodo
# On ne fait rien si pas de modif
if [ ! -e $TEMOIN ]; then
  exit 0
fi

LOCKFILE=/tmp/csvgenerate
if [ -e $LOCKFILE ];
then
  echo "Script deja en cours d execution"
  exit 1
fi

rm -f $TEMOIN
rm -f /tmp/emailunattended_generate

# initialisation de la config
# recup parametres ldap
. /etc/se3/config_l.cache.sh
. /etc/se3/config_m.cache.sh

REPSITE=/home/netlogon/domscripts
UNATTENDEDSITE=/var/se3/unattended/install/site
UNATTENDCSV=$REPSITE/unattend.csv
UNATTENDTXT=unattend.txt


##### variables a stocker dans mysql ########
if [ -e /var/se3/unattended/install/os/xp.txt ] ; then
  PRODUCTKEY="`cat /var/se3/unattended/install/os/xp.txt | grep "^PRODUCTKEY=" | cut -d= -f2 | sed 's/\r//g' | sed 's/ //g' | sed 's/:/-/g'`"
  NOMOS="`cat /var/se3/unattended/install/os/xp.txt | grep "^OSNAME=" | cut -d= -f2 | sed 's/\r//g' `"
else
  NOMOS="Windows XP Professionnel"
  PRODUCTKEY="*****-*****-*****-*****-*****"
fi
##################### creation de unattend.csv #################################
#echo "Creation de unattend.csv"
echo "\"Lookup\",\"Property\",\"Value\"" > $UNATTENDCSV
echo "\"Default\",\"OS_media\",\"xp\"" >> $UNATTENDCSV
echo "\"Default\",\"OrgName\",\"$se3_domain\"" >> $UNATTENDCSV
echo "\"Default\",\"UnattendedFile\",\"$UNATTENDTXT\"" >> $UNATTENDCSV
echo "" >> $UNATTENDCSV
echo "\"Default\",\"$NOMOS ProductID\",\"$PRODUCTKEY\"" >> $UNATTENDCSV
echo "\"Default\",\"$NOMOS ProductKey\",\"$PRODUCTKEY\"" >> $UNATTENDCSV
echo "\"Default\",\"ntp_servers\",\"ntp.ac-creteil.fr\"" >> $UNATTENDCSV
echo "\"Default\",\"top_scripts\",\"basese3.bat\"" >> $UNATTENDCSV
echo "\"Default\",\"AdminPassword\",\"wawa\"" >> $UNATTENDCSV
echo "\"Default\",\"JoinWorkgroup\",\"workgroup\"" >> $UNATTENDCSV
echo "\"Default\",\"FullName\",\"Unattended XP\"" >> $UNATTENDCSV
echo "" >> $UNATTENDCSV

export COMPUTER
export LISTEMACADD

ldapsearch -xLLL -b $computersRdn,$ldap_base_dn | grep -E "(dn: cn=|macAddress:)" | while read A
do
  if [ "`echo "$A" | grep "^dn: cn="`" == "" ]; then
	# on vient de recuperer l adresse mac
	#MACADD="$(ldapsearch -xLLL -b cn=$POSTESPARC,$computersRdn,$ldap_base_dn | grep macAddress | cut -d" " -f2)"
        MACADD=$(echo "$A" | cut -d" " -f2 | sed "s/://g" | tr '[a-f]' '[A-F]')
        # echo "ADRESSEMAC:$MACADD PC:$COMPUTER"
        # teste si adresse mac en double
        if [ "`echo "$MACADD" | grep "^[0-9A-F]*$"`" != "" ]; then
              if [ "`echo "$LISTEMACADD" | grep "$MACADD"`" == "" ]; then
                  # pas de soucis d'adresse mac en double
                  LISTEMACADD="$LISTEMACADD $MACADD"
                  
                  # a patcher dans le futur pour creer des groupes de postes (meme config). Ex : $FullName=DELLOPTIPLEX740
                  # pour l'instant : FullName=ComputerName
                  if [ "$COMPUTER" != "clone" ]; then
                      echo "\"$MACADD\",\"ComputerName\",\"$COMPUTER\"" >> $UNATTENDCSV
                      echo "\"$COMPUTER\",\"FullName\",\"$COMPUTER\"" >> $UNATTENDCSV
                      [ -e $UNATTENDEDSITE/$COMPUTER.txt ] && echo "\"$MACADD\",\"UnattendedFile\",\"$COMPUTER.txt\"" >> $UNATTENDCSV
                  fi
              else
                  echo "L'adresse MAC : $MACADD correspond a deux ordinateurs de la branche Computers (il faut corriger en ne gardant qu'une des entrees suivantes)." >> /tmp/emailunattended_generate
                  echo "$COMPUTER"  >> /tmp/emailunattended_generate
                  cat "$UNATTENDCSV" | grep "$MACADD" | cut -d, -f3 | sed 's/"//g' >> /tmp/emailunattended_generate
              fi
        fi
  else
	COMPUTER=`echo $A | cut -d= -f2 | cut -d, -f1`
  fi
done
# Envoi de l'e-mail rapport
if [ -e /tmp/emailunattended_generate ] ; then
  echo "Pour corriger : Sur l'interface web, Menu gestion des parcs, cliquer sur recherche puis supprimer les entrees obsoletes.

Dans le cas ou vous ne corrigeriez pas, les reinstallations unattended risqueraient de se faire sous le mauvais nom." >> /tmp/emailunattended_generate
#  cat  /tmp/emailunattended_generate
fi
if [ -e $UNATTENDCSV ]; then
    todos $UNATTENDCSV
    mkdir -p $UNATTENDEDSITE
    cp -f $UNATTENDCSV $UNATTENDEDSITE
fi
if [ -e $LOCKFILE ]; then
  rm $LOCKFILE
fi
exit 0


