#!/bin/bash
#shares_Win95: netlogon
#shares_Win2K: homes
#shares_WinXP: homes
#shares_Vista: homes
#shares_Seven: homes
#shares_CIFSFS: homes
#action: start
#level: 10
# $Id$
# Script de connexion destine a creer/corriger si necessaire les entrees cn=COMPUTER
# Et a renseigner la table se3db.connexions
# Adapte par S.Boireau d'apres le connexion.pl historique et ameliore ensuite d'apres le script de C.Bellegarde.
# Utilisation de nmblookup pour l'adresse MAC (d'apres Franck Molle)
#
if [ -z "$3" ]; then
	echo "Erreur d'argument."
	echo "$*"
	echo "Usage: connexion.sh utilisateur machine ip [mac]"
	exit
fi

# Pour tester l'adresse MAC meme si l'ip et le nom n'ont pas change, passer a "y" la valeur ci-dessous:
corrige_mac_si_ip_et_nom_inchange="y"
# Pour tester si l'adresse MAC doit etre corrigee quand l'ip a change, passer a "y" la valeur ci-dessous:
corrige_mac_si_ip_change="y"

# Parametres du script
user=$1

# test pour les clients linux
regex_ip='^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$'
machine=$(echo "$2" | grep -E "$regex_ip")

if [ -z "$machine" ]; then
    machine=$(echo "$2" | tr 'A-Z' 'a-z')
else
    machinetmp=`nmblookup -A $machine | sed -n '2p' | cut -d' ' -f1 | sed 's/^[ \t]*//;s/[ \t]*$//'`
    machine=$(echo "$machinetmp" | tr 'A-Z' 'a-z')
fi

ip=$3

if [ "$machine" == "clone" ]; then
    exit 0
fi
if echo "$4" | grep -q -E ":|-" ; then
    newmac=$(echo "$4" | sed "s/-/:/g" | tr '[A-F]' '[a-f]')
fi
# Dossier/fichier de log si nécessaire
DOSS_SE3LOG=/var/log/se3
mkdir -p $DOSS_SE3LOG
SE3LOG=$DOSS_SE3LOG/connexions.log

# recup parametres mysql
. /etc/se3/config_o.cache.sh

# recup parametres ldap
. /etc/se3/config_l.cache.sh

GET_MAC_FROM_IP()
{
if [ -z "$newmac" ]; then
	newmac=$(nmblookup -A $1 | awk  '/MAC Address/ {print  $4}' | sed -e "s/-/:/g")
	if [ "$newmac" == "00:00:00:00:00:00" ]; then 
		newmac=$(arp -n $1 | awk '/ether/ { print $3 }')
	fi
	echo $newmac
	
else
       	echo $newmac
fi
}

VIRE_DN()
{
    # on verifie l'existence de doublons et vire les enregistrements dn
	# attention : il faut aussi faire le menage dans les parcs, wpkg, italc, les imprimantes....
    local attr=$1 
    local valeur=$2
    local olddnlist=$(ldapsearch -xLLL -b ${computersRdn},${ldap_base_dn} "($attr=$valeur)" dn | sed -e "s/^dn: //g;/^$/d")
    if [ -n "$olddnlist" ]; then
        echo -e "$olddnlist" | while read olddn
	do
            echo "dn: $olddn
changetype: delete
"
        done
    fi
}
VIRE_ATTR()
{
    # on verifie l'existence d'attributs en double et on les vire

    local attr=$1 
    local valeur=$2
    local cn=$3 
    local res=$(ldapsearch -xLLL -b ${computersRdn},${ldap_base_dn} "(&(!(cn=$cn))($attr=$valeur))" $attr | egrep "(^dn:|^$attr:)")
    if [ -n "$res" ]; then
        echo -e "$res" | sed -e "s/^$attr:/changetype: modify\ndelete: $attr\n$attr:/g"
    fi
}            

# Dossier dans lequel creer les fichiers LDIF temporaires de correction
tmp=/var/lib/se3/connexion_ldif
mkdir -p ${tmp}
# Fichier des modifs LDAP
ldif_modif=$tmp/${machine}_$RANDOM.ldif
# La creation d'un fichier est source de lenteur... cela dit, on ne fait normalement pas la modif de l'annuaire frequemment.

# Recherche LDAP de la machine dans la branche ou=Computers
# ---------------------------------------------------------

#ldapsearch -xLLL -b ou=Computers,${ldap_base_dn} cn=$machine
OLDIFS=$IFS
IFS="
"
tst=($(ldapsearch -xLLL -b ${computersRdn},${ldap_base_dn} cn=$machine ipHostNumber macAddress | egrep "(ipHostNumber|macAddress)"))
IFS=$OLDIFS
if [ "${#tst[*]}" == "0" ]; then
	# La machine n'est pas dans l'annuaire

	mac=$(GET_MAC_FROM_IP $ip)
	if [ -z "$mac" ]; then
		mac="--"
	else
        # on verifie qu'elle n'a pas changé de nom : on cherche les cn correspondants à l'@ mac
        VIRE_DN macAddress $mac > ${ldif_modif} 
    fi
    echo "
dn: cn=$machine,${computersRdn},${ldap_base_dn}
cn: $machine
objectClass: top
objectClass: ipHost
objectClass: ieee802Device
objectClass: organizationalRole
ipHostNumber: $ip
macAddress: $mac
" >> ${ldif_modif}

	# Decommenter la ligne pour debug et lancer /usr/share/se3/sbin/connexion.sh admin NOM_MACHINE IP:
#	cat ${ldif_modif}
	ldapadd -x -c -D ${adminRdn},${ldap_base_dn} -w ${adminPw} -f ${ldif_modif} > /dev/null 2>&1
	touch /tmp/csvtodo
else
	# La machine est dans l'annuaire
	cpt=0
	# Normalement on a que deux lignes dans le tableau:
	while [ $cpt -lt ${#tst[*]} ]
	do
		attribut=${tst[$cpt]}
		#echo "attribut=$attribut"
		if [ "${attribut:0:14}" == "ipHostNumber: " ]; then
			ipHostNumber=${attribut:14}
		else
			if [ "${attribut:0:12}" == "macAddress: " ]; then
				macAddress=${attribut:12}
			fi
		fi

		cpt=$(($cpt+1))
	done

	#echo "ipHostNumber=$ipHostNumber"
	#echo "macAddress=$macAddress"

	if [ -n "$ip" ]; then
		if [ "$ip" == "$ipHostNumber" ]; then
			if [ "$corrige_mac_si_ip_et_nom_inchange" == "y" ]; then
				mac=$(GET_MAC_FROM_IP $ip)

				# Controle de l'adresse MAC:
				# Si l'adresse MAC differe sans etre vide, on met a jour
				# (au cas ou on aurait change de machine ou de carte reseau
				# ou si la machine ne repondrait plus au ping)
				if [ "$mac" != "$macAddress" -a -n "$mac" ]; then
				    # on vire des enregistrements eventuels avec cette @mac
				    VIRE_ATTR macAddress $mac $machine > ${ldif_modif}
					echo "
dn: cn=$machine,${computersRdn},${ldap_base_dn}
changetype: modify
replace: macAddress
macAddress: $mac
-" >> ${ldif_modif}
					ldapmodify -x -c -D ${adminRdn},${ldap_base_dn} -w ${adminPw} -f ${ldif_modif}  > /dev/null 2>&1
					touch /tmp/csvtodo
				fi
			fi
		else
			# L'adresse IP a change
			# on nettoie les enregistrements avec l'ancienne
			VIRE_ATTR ipHostNumber $ip $machine > ${ldif_modif}
			echo "
dn: cn=$machine,${computersRdn},${ldap_base_dn}
changetype: modify
replace: ipHostNumber
ipHostNumber: $ip
-" >> ${ldif_modif}

			if [ "$corrige_mac_si_ip_change" == "y" ]; then
				mac=$(GET_MAC_FROM_IP $ip)

				if [ -n "$mac" ]; then
					echo "replace: macAddress
macAddress: $mac
-" >> ${ldif_modif}
				fi
			fi

			ldapmodify -x -c -D ${adminRdn},${ldap_base_dn} -w ${adminPw} -f ${ldif_modif}  > /dev/null 2>&1
			touch /tmp/csvtodo
		fi
	else
		# Ca ne devrait pas arriver... l'entree existe mais avec une adresse IP vide???
        # on fait le menage
        VIRE_ATTR ipHostNumber $ip $machine > ${ldif_modif}
	echo >> ${ldif_modif}
        VIRE_ATTR macAddress $mac $machine >> ${ldif_modif}
		echo "
dn: cn=$machine,${computersRdn},${ldap_base_dn}
changetype: modify
replace: ipHostNumber
ipHostNumber: $ip
-" >> ${ldif_modif}

		mac=$(GET_MAC_FROM_IP $ip)

		if [ -n "$mac" ]; then
			echo "replace: macAddress
macAddress: $mac
-" >> ${ldif_modif}
		fi

		ldapmodify -x -c -D ${adminRdn},${ldap_base_dn} -w ${adminPw} -f ${ldif_modif}  > /dev/null 2>&1
		touch /tmp/csvtodo
	fi

fi

if [ -e "${ldif_modif}" ]; then
	# Pour executer a la main le script /usr/share/se3/sbin/connexion.sh toto xpbof 172.16.123.4
	# et suivre les modifs, decommenter les lignes ci-dessous:
	#echo ""
	#cat ${ldif_modif}
	#echo ""
    if [  "$DEBUG" != "1" ]; then
        rm -f ${ldif_modif}
    fi
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

