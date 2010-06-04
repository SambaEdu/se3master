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
		
if [ "$2" = "" -a "$1" != "_" ]
then 
	echo "USAGE: Vous devez indiquer un parc existant et une action valide parmi wol, reboot, stop:"
	echo "       Exemple: $0 salle_profs wol"
#	echo "Les parcs existants sont :"
#	ldapsearch  -x -b $PARCSRDN,$BASEDN '(objectclass=*)'  | grep cn |  grep -v requesting | grep -i -v Rights | grep -i -v member 
else
	if [ "$1" = "_" ]; then
		tmp=/tmp/liste_parcs_$(date +%Y%m%d%H%M%S)_${RANDOM}
		touch $tmp
		chmod 700 $tmp
		parc=""
		while [ -z "$parc" ]
		do
			echo "Sur quel parc souhaitez-vous agir?"
			ldapsearch -xLLL -b $PARCSRDN,$BASEDN cn | grep "^cn: " | sed -e "s|^cn: ||" | sort > $tmp
			cpt=0
			while read A
			do
				tab_parcs[$cpt]=$A
				echo "   $cpt : $A"
				cpt=$(($cpt+1))
			done < $tmp
			echo -e "Parc: \c"
			read num_parc

			if [ -n "${tab_parcs[$num_parc]}" ]; then
				parc=${tab_parcs[$num_parc]}
			fi
		done
		rm -f $tmp

		action=""
		echo "Quelle action souhaitez-vous appliquer au parc $parc?"
		while [ -z "$action" ]
		do
			echo "   1 : wol (eveil)"
			echo "   2 : stop ou shutdown (extinction)"
			echo "   3 : reboot (reboot)"
			echo -e "Action: \c"
			read num_action

			case $num_action in
				1)
					action='wol'
				;;
				2)
					action='stop'
				;;
				3)
					action='reboot'
				;;
			esac
		done
	else
		parc=$1
		action=$2

		if [ "$action" != "wol" -a "$action" != "shutdown" -a "$action" != "stop" -a "$action" != "reboot" ]; then
			echo "Action invalide pour le parc ${parc} : ${action}"
			exit
		fi
	fi

	echo "<h1>Action sur le parc ${parc} : ${action}</h1><br>"

	ldapsearch  -xLLL -b cn=${parc},$PARCSRDN,$BASEDN '(objectclass=groupOfNames)' member | grep member | while read A
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
						if [ "${action}" = "shutdown" -o "${action}" = "stop" ]; then
							echo "Tentative d'arret de la machine XP/2000<b> $B</b> correspondant a l'adresse mac <b>$D</b><br>"
							/usr/bin/net rpc shutdown -C "Shutdown" -S $B -U "$B\adminse3%$PASSADM"
						fi

						if [ "${action}" = "reboot" ]; then
							echo "Tentative de reboot de la machine XP/2000<b> $B</b> correspondant a l'adresse mac <b>$D</b><br>"
							/usr/bin/net rpc shutdown -r -C "Reboot" -S $B -U "$B\adminse3%$PASSADM"
						fi

						if [ "${action}" = "wol" ]; then
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
							if [ "${action}" = "wol" ]; then
								ldapsearch  -xLLL -b cn=$B,$COMPUTERSRDN,$BASEDN '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g;s/\.[0-9]*$/.255/g" | while read I                                                        do
								do
									echo "Tentative d'eveil pour la machine Win9x/Linux <b>$B</b> correspondant a l'adresse mac <b>$D</b><br>"
									/usr/bin/wakeonlan -i $I $D > /dev/null
									/usr/bin/wakeonlan $D > /dev/null
								done    
							fi
							if [ "${action}" = "shutdown" -o "${action}" = "stop" ]; then
								echo "Tentative d'arret de la machineWin9x/Linux <b>$B</b> correspondant a l'adresse mac <b>$D</b><br>"
								# Prevoir de recup l'adresse IP car $B correspond au nom mais sous linux pour le moment cela est son IP :(
								/usr/bin/ssh -o StrictHostKeyChecking=no $B halt
							fi
							if [ "${action}" = "reboot" ]; then
								echo "Tentative de reboot de la machineWin9x/Linux <b>$B</b> correspondant a l'adresse mac <b>$D</b><br>"
								# Prevoir de recup l'adresse IP car $B correspond au nom mais sous linux pour le moment cela est son IP :(
								/usr/bin/ssh -o StrictHostKeyChecking=no $B reboot
							fi
						fi
					fi
				done
			done
		done
	done
fi
