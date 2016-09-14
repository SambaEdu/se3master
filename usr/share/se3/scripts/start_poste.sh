#!/bin/bash
# SambaEdu
#
# $Id$
#

WWWPATH="/var/www"

# recup parametres ldap
. /etc/se3/config_l.cache.sh
# recup parametres caches : 
. /etc/se3/config_m.cache.sh
. /etc/se3/config_d.cache.sh

# calcule l'adresse de broadcast à partir de l'ip
GETBROADCAST()
{
    if [ "$dhcp" == "1" ]; then
        vlan=
    else
        broadcast=$(echo $1|"s/\.[0-9]*$/.255/")
    fi
    
}
if [ -z "$2" ]
then
	echo "Ce script est destine a provoquer l'allumage,"
	echo "l'extinction ou le reboot d'une machine."
	echo "USAGE: Passer en parametres le nom netbios de la machine et l'action."
	echo "       L'action doit etre shutdown, reboot ou wol."
	echo "       Exemple: $0 CDI01 reboot"
#	echo "Les parcs existants sont :"
#	ldapsearch  -x -b $PARCSRDN,$BASEDN '(objectclass=*)'  | grep cn |  grep -v requesting | grep -i -v Rights | grep -i -v member
else
	ldapsearch  -xLLL -b ${computersRdn},${ldap_base_dn} cn=$1 '(objectclass=*)' macAddress | grep macAddress | while read C
	do
		echo "$C" | cut -d: -f 2-7  | while read D
		do
			getent passwd $1$>/dev/null && TYPE="XP"
			if [ "$TYPE" = "XP" ]; then
				echo "<br><h2>Action sur : $1</h2><br>"
				if [ "$2" = "shutdown" -o "$2" = "stop"  ]; then
					echo "<h3>Tentative d'arrêt de la machine $1</h3><br>"
                                        ldapsearch  -xLLL -b ${computersRdn},${ldap_base_dn} cn=$1 '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g" | while read I
                                        do
 						/usr/bin/net rpc shutdown -t 30 -f -C "Arrêt demande par le serveur sambaEdu3" -I $I -U "$1\adminse3%$xppass"
					done
				fi
				if [ "$2" = "reboot" ]; then
					echo "<h3>Tentative de reboot de la machine $1</h3><br>"
                                        ldapsearch  -xLLL -b ${computersRdn},${ldap_base_dn} cn=$1 '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g" | while read I
                                        do
                                                /usr/bin/net rpc shutdown -t 30 -r -f -C "Arrêt demande par le serveur sambaEdu3" -I $I -U "$1\adminse3%$xppass"
                                        done
				fi
				if [ "$2" = "wol" ]; then
					echo "Tentative d'éveil pour la machine correspondant à l'adresse mac $D<br>"
				        ldapsearch  -xLLL -b ${computersRdn},${ldap_base_dn} cn=$1 '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g;s/\.[0-9]*$/.255/g" | while read I
	                                do
					       echo "Broadcast: $I<br>"
					       /usr/bin/wakeonlan  -i $I $D > /dev/null
					       /usr/bin/wakeonlan   $D > /dev/null
					done
				fi
			else
				# On teste si on a un windows ou un linux
				ldapsearch  -x -b ${computersRdn},${ldap_base_dn} uid=$B$ '(objectclass=*)' uidNumber | grep uid |grep -v requesting | grep -v base
				# On peut penser que l'on a un linux, mais cela peut aussi être un win 9X
				# A affiner
				if [ $? = "1" ]
				then
				if [ "$2" = "wol" ]; then
					echo "Tentative d'éveil pour la machine correspondant à l'adresse mac $D<br>"
				        ldapsearch  -xLLL -b ${computersRdn},${ldap_base_dn} cn=$1 '(objectclass=ipHost)' ipHostNumber | grep ipHostNumber: | sed "s/ipHostNumber: //g;s/\.[0-9]*$/.255/g" | while read I
	                                do
					       echo "broadcast: $I<br>"
					       /usr/bin/wakeonlan  -i $I $D > /dev/null
					       /usr/bin/wakeonlan   $D > /dev/null
					done
				fi

					if [ "$2" = "shutdown" -o "$2" = "stop" ]; then
						echo "<h3>Tentative d'arret de la machine $1</h3><br>"
						/usr/bin/ssh -o StrictHostKeyChecking=no $1 halt
					fi
				fi
			fi
		done
	done
fi
