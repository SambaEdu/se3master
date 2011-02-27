#!/bin/sh
# SambaEdu
#
# $Id: mail-ldap.sh 341 2005-07-13 15:06:30Z plouf $
#
 
WWWPATH="/var/www"

if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
        dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible"
        exit 1
fi
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
        echo "Impossible d'accéder au paramètre BASEDN"
        exit 1
fi

COMPUTERSRDN=`echo "SELECT value FROM params WHERE name='ComputersRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$COMPUTERSRDN" ]; then
        echo "Impossible d'accéder au paramètre COMPUTERSRDN"
        exit 1
fi
		
PARCSRDN=`echo "SELECT value FROM params WHERE name='parcsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PARCSRDN" ]; then
        echo "Impossible d'accéder au paramètre PARCSDN"
        exit 1
fi
PASSADM=`echo "SELECT value FROM params WHERE name='xppass'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PASSADM" ]; then
        echo "Impossible d'accéder au paramètre PASSADM"
	exit 1
fi
		
if [ "$1" == "" ]
then 
	echo "vous devez indiquer un parc existant"
#	echo "Les parcs existants sont :"
#	ldapsearch  -x -b $PARCSRDN,$BASEDN '(objectclass=*)'  | grep cn |  grep -v requesting | grep -i -v Rights | grep -i -v member 
else
echo "<h1>Action sur le parc $1 : $2</h1><br>"

ldapsearch  -x -b cn=$1,$PARCSRDN,$BASEDN '(objectclass=groupOfNames)' member | grep member | grep -v requesting | while read A
	do
#echo "pour la machine $A"
echo "$A" | cut -d= -f2 | cut -d, -f1 | while read B

do

#echo  "ldapsearch  -x -b cn=$B,$COMPUTERSRDN,$BASEDN '(objectclass=*)' macAddress | grep macAddress | grep -v requesting" 


ldapsearch  -x -b cn=$B,$COMPUTERSRDN,$BASEDN '(objectclass=*)' ipHostNumber | grep ipHostNumber | grep -v requesting | while read C
	do
#		echo "pour la machine $C"
echo "$C" | cut -d: -f2 | while read D
        do
getent passwd $B$>/dev/null && TYPE="XP" 
  if [ "$TYPE" = "XP" ]; then
	  if [ "$2" = "verif" ]; then
echo "<br><h2>Verification de : $B</h2><br>"
#if [ -e /var/se3/unattended/install/computers/$B.tmp ]; then
# cp /var/se3/unattended/install/computers/$B.tmp  /var/se3/unattended/install/computers/$B.tmp.old
#fi
#echo " cp /var/se3/unattended/install/computers/$B.tmp  /var/se3/unattended/install/computers/$B.tmp.old"
	#echo "ssh adminse3@$C ipconfig"
ssh -o StrictHostKeyChecking=no adminse3@$D /testssh.cmd 
 logger -p local5.warn -t "Clients" "Validation du client $B"
	fi

	 if [ "$2" = "install" ]; then
echo "<br><h2>Installation sur : $B</h2><br>"
		 if [ -e /var/se3/unattended/install/computers/$B.todo ]; then
#echo "passage dans la boucle "
cp /var/se3/unattended/install/computers/$B.todo /var/se3/unattended/install/computers/$B.todo.tmp
			 scp -o StrictHostKeyChecking=no /var/se3/unattended/install/computers/$B.todo adminse3@$D:/todo.txt 
			 logger -p local5.warn -t "Clients" "Envoi du fichier todo.txt vers $B"
			 
#mv /var/se3/unattended/install/computers/$B.txt	/var/se3/unattended/install/computers/$B.txt.make
		ssh -o StrictHostKeyChecking=no adminse3@$D /todo.cmd >> /var/se3/unattended/install/computers/$B.log 
		 logger -p local5.warn -t "Clients" "Execution de todo.pl sur $B"
	#	 if [  -e /var/se3/unattended/install/computers/$B.old]; then
	#	 rm /var/se3/unattended/install/computers/$B.txt
	# 	fi
	rm /var/se3/unattended/install/computers/$B.todo.tmp
#	rm /var/se3/unattended/install/computers/$B.todo
	

fi
	fi

fi
done
done
done
done
fi
