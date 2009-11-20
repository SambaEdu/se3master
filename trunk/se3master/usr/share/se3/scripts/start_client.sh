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

	ldapsearch  -xLLL -b cn=$1,$PARCSRDN,$BASEDN '(objectclass=groupOfNames)' member | grep member | while read A
	do
		#echo "pour la machine $A"
		echo "$A" | cut -d= -f2 | cut -d, -f1 | while read B
		do
			
			#echo  "ldapsearch  -x -b cn=$B,$COMPUTERSRDN,$BASEDN '(objectclass=*)' macAddress | grep macAddress | grep -v requesting" 
			
			ldapsearch  -xLLL -b cn=$B,$COMPUTERSRDN,$BASEDN '(objectclass=*)' macAddress | grep macAddress | while read C
			do
				echo "$C" | cut -d: -f 2-7  | while read D
				do
					getent passwd $B$>/dev/null && TYPE="XP" 
					if [ "$TYPE" = "XP" ]; then
						echo "<br><h3>Action sur : $B</h3>"
						#============================================
						# MODIF: S.Boireau 20/02/2006
						#if [ "$2" = "shutdown" ]; then
						if [ "$2" = "shutdown" -o "$2" = "stop" ]; then
							echo "Tentative d'arret de la machine XP/2000<b> $B</b> correspondant a l'adresse mac <b>$D</b><br>"
							/usr/bin/net rpc shutdown -C "Shutdown" -S $B -U "$B\adminse3%$PASSADM"
						fi
						if [ "$2" = "wol" ]; then
						        ldapsearch  -xLLL -b cn=$B,$COMPUTERSRDN,$BASEDN '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g;s/\.[0-9]*$/.255/g" | while read I                                                        do
							do
							    echo "Tentative d'eveil pour la machine correspondant a l'adresse mac $D et au broadcast $I<br>"
                                                            /usr/bin/wakeonlan -i $I $D > /dev/null
                                                            /usr/bin/wakeonlan $D > /dev/null
							done    
      						fi
					
					else
                                                # On teste si on a un windows ou un linux
	                                         ldapsearch  -xLLL -b uid=$B$,$COMPUTERSRDN,$BASEDN '(objectclass=*)' uidNumber | grep uid
	                                        # On peut penser que l'on a un linux, mais cela peut aussi être un win 9X
	                                        # A affiner
	                                        if [ $? = "1" ]
	                                        then
						echo "<br><h3>Action sur : $B</h3>"
	 	                                      if [ "$2" = "wol" ]; then
			   			              ldapsearch  -xLLL -b cn=$B,$COMPUTERSRDN,$BASEDN '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g;s/\.[0-9]*$/.255/g" | while read I                                                        do
							      do
			                                          echo "Tentative d'eveil pour la machine Win9x/Linux <b>$B</b> correspondant a l'adresse mac <b>$D</b><br>"
                                                                  /usr/bin/wakeonlan -i $I $D > /dev/null
                                                                  /usr/bin/wakeonlan $D > /dev/null
							      done    
			                              fi
	                                              if [ "$2" = "shutdown" -o "$2" = "stop" ]; then
						              echo "Tentative d'arret de la machineWin9x/Linux <b>$B</b> correspondant a l'adresse mac <b>$D</b><br>"
						              # Prevoir de recup l'adresse IP car $B correspond au nom mais sous linux pour le moment cela est son IP :(
							      /usr/bin/ssh -o StrictHostKeyChecking=no $B halt
						      fi
						fi
					fi
				done
			done
		done
	done
fi
