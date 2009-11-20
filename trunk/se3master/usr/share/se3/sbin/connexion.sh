#!/bin/bash

# $Id$
#
# Script de connexion destine a creer/corriger si necessaire les entrees cn=COMPUTER
# Et a renseigner la table se3db.connexions
# Adapte par S.Boireau d'apres le connexion.pl historique et ameliore ensuite d'apres le script de C.Bellegarde.
# Utilisation de nmblookup pour l'adresse MAC (d'apres Franck Molle)
#
# Derniere modif: 29/11/2008

if [ -z "$3" ]; then
	echo "Erreur d'argument."
	echo "$*"
	echo "Usage: connexion.sh utilisateur machine ip"
	exit
fi

# Pour tester l'adresse MAC meme si l'ip et le nom n'ont pas change, passer a "y" la valeur ci-dessous:
corrige_mac_si_ip_et_nom_inchange="y"
# Pour tester si l'adresse MAC doit etre corrigee quand l'ip a change, passer a "y" la valeur ci-dessous:
corrige_mac_si_ip_change="y"

# Parametres du script
user=$1
machine=$2
ip=$3

# Dossier/fichier de log si nécessaire
DOSS_SE3LOG=/var/log/se3
mkdir -p $DOSS_SE3LOG
SE3LOG=$DOSS_SE3LOG/connexions.log

WWWPATH="/var/www"
if [ -e ${WWWPATH}/se3/includes/config.inc.php ]; then
	dbhost=`cat ${WWWPATH}/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat ${WWWPATH}/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	dbuser=`cat ${WWWPATH}/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	dbpass=`cat ${WWWPATH}/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible" | tee -a $SE3LOG
	exit 1
fi

#
# Recuperation des params LDAP
#

#BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
#ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
#PASSDN=$(cat /etc/ldap.secret)

OLDIFS=$IFS
IFS="
"
# Pas sur que mon tableau en BASH permette de gagner du temps par rapport aux requetes mysql economisees...
tab=($(echo "SELECT name,value FROM params WHERE name='ldap_base_dn' or name='computersRdn' or name='adminRdn' or name='adminPw';" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N | tr "\t" " " | sed -e "s/ \{2,\}/ /g"))
cpt=0
while [ $cpt -lt ${#tab[*]} ]
do
	ligne=${tab[$cpt]}
	#echo "ligne=$ligne"
	if [ "${ligne:0:13}" = "ldap_base_dn " ]; then
		BASEDN=${ligne:13}
	else
		if [ "${ligne:0:13}" = "computersRdn " ]; then
			COMPUTERSRDN=${ligne:13}
		else
			if [ "${ligne:0:9}" = "adminRdn " ]; then
				ADMINRDN=${ligne:9}
			else
				if [ "${ligne:0:8}" = "adminPw " ]; then
					ADMINPW=${ligne:8}
				fi
			fi
		fi
	fi
	cpt=$(($cpt+1))
done
IFS=$OLDIFS

#BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "${BASEDN}" ]; then
	echo "Impossible d'acceder au parametre BASEDN" | tee -a $SE3LOG
	exit 1
fi
#COMPUTERSRDN=`echo "SELECT value FROM params WHERE name='computersRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "${COMPUTERSRDN}" ]; then
	echo "Impossible d'acceder au parametre COMPUTERSRDN" | tee -a $SE3LOG
	exit 1
fi
#ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINRDN" ]; then
	echo "Impossible d'acceder au parametre ADMINRDN" | tee -a $SE3LOG
	exit 1
fi
#ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINPW" ]; then
	echo "Impossible d'acceder au parametre ADMINPW" | tee -a $SE3LOG
	exit 1
fi

GET_MAC_FROM_IP()
{
	# Methode historique
	#	ping -c1 $1 > /dev/null 2>&1
	#	#arp=$(expr "$(/usr/sbin/arp -n $1 | grep $1)" : '.*\(..:..:..:..:..:..\).*')
	#	#echo $arp
	#	expr "$(/usr/sbin/arp -n $1 | grep $1)" : '.*\(..:..:..:..:..:..\).*'
	# Methode Franck Molle
	#nmblookup -A $1 | grep MAC | awk  '{print  $4}'
	# Correction d'apres JLB
	nmblookup -A $1 | awk  '/MAC Address/ {print  $4}' | sed -e "s/-/:/g"
}

# Dossier dans lequel creer les fichiers LDIF temporaires de correction
tmp=/var/lib/se3/connexion_ldif
mkdir -p ${tmp}
# Fichier des modifs LDAP
ldif_modif=$tmp/${machine}_$RANDOM.ldif
# La creation d'un fichier est source de lenteur... cela dit, on ne fait normalement pas la modif de l'annuaire frequemment.

# Recherche LDAP de la machine dans la branche ou=Computers
# ---------------------------------------------------------

#ldapsearch -xLLL -b ou=Computers,${BASEDN} cn=$machine
OLDIFS=$IFS
IFS="
"
tst=($(ldapsearch -xLLL -b ${COMPUTERSRDN},${BASEDN} cn=$machine ipHostNumber macAddress | egrep "(ipHostNumber|macAddress)"))
IFS=$OLDIFS
if [ "${#tst[*]}" = "0" ]; then
	# La machine n'est pas dans l'annuaire

	mac=$(GET_MAC_FROM_IP $ip)
	if [ -z "$mac" ]; then
		mac="--"
	fi

	echo "dn: cn=$machine,${COMPUTERSRDN},${BASEDN}
cn: $machine
objectClass: top
objectClass: ipHost
objectClass: ieee802Device
objectClass: organizationalRole
ipHostNumber: $ip
macAddress: $mac
" > ${ldif_modif}

	# Decommenter la ligne pour debug et lancer /usr/share/se3/sbin/connexion.sh admin NOM_MACHINE IP:
	#cat ${ldif_modif}
	ldapadd -x -D ${ADMINRDN},${BASEDN} -w ${ADMINPW} -f ${ldif_modif}
else
	# La machine est dans l'annuaire
	cpt=0
	# Normalement on a que deux lignes dans le tableau:
	while [ $cpt -lt ${#tst[*]} ]
	do
		attribut=${tst[$cpt]}
		#echo "attribut=$attribut"
		if [ "${attribut:0:14}" = "ipHostNumber: " ]; then
			ipHostNumber=${attribut:14}
		else
			if [ "${attribut:0:12}" = "macAddress: " ]; then
				macAddress=${attribut:12}
			fi
		fi

		cpt=$(($cpt+1))
	done

	#echo "ipHostNumber=$ipHostNumber"
	#echo "macAddress=$macAddress"

	if [ -n "$ip" ]; then
		if [ "$ip" = "$ipHostNumber" ]; then
			if [ "$corrige_mac_si_ip_et_nom_inchange" = "y" ]; then
				mac=$(GET_MAC_FROM_IP $ip)

				# Controle de l'adresse MAC:
				# Si l'adresse MAC differe sans etre vide, on met a jour
				# (au cas ou on aurait change de machine ou de carte reseau
				# ou si la machine ne repondrait plus au ping)
				if [ "$mac" != "$macAddress" -a -n "$mac" ]; then
					echo "dn: cn=$machine,${COMPUTERSRDN},${BASEDN}
changetype: modify
replace: macAddress
macAddress: $mac" > ${ldif_modif}
					ldapmodify -x -D ${ADMINRDN},${BASEDN} -w ${ADMINPW} -f ${ldif_modif}
				fi
			fi
		else
			# L'adresse IP a change
			echo "dn: cn=$machine,${COMPUTERSRDN},${BASEDN}
changetype: modify
replace: ipHostNumber
ipHostNumber: $ip" > ${ldif_modif}

			if [ "$corrige_mac_si_ip_change" = "y" ]; then
				mac=$(GET_MAC_FROM_IP $ip)

				if [ -n "$mac" ]; then
					echo "-
replace: macAddress
macAddress: $mac" >> ${ldif_modif}
				fi
			fi

			ldapmodify -x -D ${ADMINRDN},${BASEDN} -w ${ADMINPW} -f ${ldif_modif}
		fi
	else
		# Ca ne devrait pas arriver... l'entree existe mais avec une adresse IP vide???

		echo "dn: cn=$machine,${COMPUTERSRDN},${BASEDN}
changetype: modify
replace: ipHostNumber
ipHostNumber: $ip" > ${ldif_modif}

		mac=$(GET_MAC_FROM_IP $ip)

		if [ -n "$mac" ]; then
			echo "-
replace: macAddress
macAddress: $mac
" >> ${ldif_modif}
		fi

		ldapmodify -x -D ${ADMINRDN},${BASEDN} -w ${ADMINPW} -f ${ldif_modif}
	fi

fi

if [ -e "${ldif_modif}" ]; then
	# Pour executer a la main le script /usr/share/se3/sbin/connexion.sh toto xpbof 172.16.123.4
	# et suivre les modifs, decommenter les lignes ci-dessous:
	#echo ""
	#cat ${ldif_modif}
	#echo ""
	rm -f ${ldif_modif}
	# Pour conserver une trace des operations, on peut commenter la ligne de suppression du LDIF.
	#echo "Correction $machine;$ip;$mac le $(date +%Y%m%d-%H%M%S)" >> $SE3LOG
fi

# Insertion dans la table MySQL de la connexion de l'utilisateur sur cette machine
echo "insert into connexions
set username='$user',
ip_address='$ip',
netbios_name = '$machine',
logintime=now();" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass

exit 0

