#!/bin/bash
#
##### Script de génération du fichier de correspondance NOM;IP;MAC #####
#
# Auteur : Stephane Boireau (Bernay/Pont-Audemer (27))
#
## $Id$ ##
#
# Dernière modif: 25/03/2007

if [ "$1" = "--help" -o "$1" = "-h" ]; then
	echo "Script permettant de générer un fichier de correspondances NOM;IP;MAC pour"
	echo "l'outil de post-clonage."
	echo ""
	echo "Usage : Pas d'option."
	exit
fi


ladate=$(date '+%Y%m%d-%H%M%S')

if [ -e "/var/se3/Progs/install/installdll" ]; then
	dest="/var/se3/Progs/install/installdll"
else
	dest="/root/tmp/correspondances_nom_ip_mac_${ladate}"
fi
mkdir -p ${dest}

fich_nom_ip_mac="$dest/correspondances_nom_ip_mac_${ladate}.txt"
fich_clients_ini="$dest/clients_ini_${ladate}.txt"

BASE=$(grep "^BASE" /etc/ldap/ldap.conf | cut -d" " -f2 )
ldapsearch -xLLL -b ou=computers,$BASE cn | grep ^cn | cut -d" " -f2 | while read nom
do
	if [ ! -z $(echo ${nom:0:1} | sed -e "s/[0-9]//g") ]; then
		# PB: on récupère les cn des entrées machines aussi (xpbof et xpbof$)
		ip=$(ldapsearch -xLLL -b ou=computers,$BASE cn=$nom ipHostNumber | grep ipHostNumber | cut -d" " -f2)
		mac=$(ldapsearch -xLLL -b ou=computers,$BASE cn=$nom macAddress | grep macAddress | cut -d" " -f2)

		if [ ! -z "$ip" -a ! -z "$mac" ]; then
			echo "$nom;$mac;$ip;" >> $fich_nom_ip_mac
			echo "$nom=$mac" >> $fich_clients_ini
		fi
	fi
done

# Conversion en fichier DOS.
#cat /home/templates/$1/logon_Win2K.bat | perl -pe 's/\n/\r\n/' > /home/templates/$1/logon_Win2K.bat
sort ${fich_nom_ip_mac} > ${fich_nom_ip_mac}.tmp
cat ${fich_nom_ip_mac}.tmp | perl -pe 's/\n/\r\n/' > $fich_nom_ip_mac
rm -f ${fich_nom_ip_mac}.tmp

sort ${fich_clients_ini} > ${fich_clients_ini}.tmp
cat ${fich_clients_ini}.tmp | perl -pe 's/\n/\r\n/' > $fich_clients_ini
rm -f ${fich_clients_ini}.tmp

echo "Les fichiers ont été générés dans $dest"
echo "Terminé."
