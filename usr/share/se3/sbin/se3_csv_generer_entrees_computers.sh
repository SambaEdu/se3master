#!/bin/bash

#
## $Id$ ##
#
##### Import des entrees computers d apres un CSV IP;NOM;MAC #####
#
# Import des entrees computers d apres un CSV IP;NOM;MAC

# Stephane Boireau

if [ -z "$1" -o ! -e "$1" ]; then
	echo "USAGE: Fournir un CSV des machines:"
	echo "         IP;NOM;MAC"
	echo "       Eventuellement:"
	echo "         IP;NOM;MAC;PARC"
	echo ""
	echo "       Un fichier au format IP;NOM;MAC peut etre genere en effectuant un export"
	echo "       des reservations DHCP (se3-dhcp)."
	exit
fi
DHCPCSV=$1

. /usr/share/se3/sbin/variables_admin_ldap.sh lib > /dev/null
if [ -z "$ROOTDN" ]; then
	echo "ERREUR: La version de variables_admin_ldap.sh n'est pas assez recente."
	echo "        Les variables ROOTDN, PASSDN,... ne sont pas initialisees."
	exit
fi

tmp=/root/tmp/creation_entrees_machines_d_apres_un_csv_$(date +%Y%m%d%H%M%S)
mkdir -p $tmp

while read A
do
	IP=$(echo "$A"|cut -d";" -f1)
	NOM=$(echo "$A"|cut -d";" -f2|tr "[A-Z]" "[a-z]")
	MAC=$(echo "$A"|cut -d";" -f3|tr "[a-z]" "[A-Z]")
	PARC=$(echo "$A"|cut -d";" -f4)

	#t=$(echo "$IP"|sed -e "s|[0-9.]||g")
	t=$(echo "$IP"|sed -e "s|[0-9]\{1,\}\.[0-9]\{1,\}\.[0-9]\{1,\}\.[0-9]\{1,\}||g")
	if [ -n "$t" ]; then
		IP=""
	fi

	t=$(echo "$NOM"|sed -e "s|[a-zA-Z0-9_.]||g")
	if [ -n "$t" ]; then
		NOM=""
	fi

	t=$(echo "$MAC"|sed -e "s|[A-F0-9:]||g")
	if [ -n "$MAC" ]; then
		MAC=""
	fi

	t=$(echo "$PARC"|sed -e "s|[a-zA-Z0-9_.]||g")
	if [ -n "$t" ]; then
		PARC=""
	fi

	if [ -n "$IP" -a -n "$NOM" -a -n "$MAC" ]; then
		# Inutile de remplir le UNATTENDCSV:
		# Il faut juste les entrees dans l'annuaire.
		# Pour le reste:
		# - importer le CSV dans DHCP
		# - lancer unattended-generate.sh
		# Creer un parc et y mettre ces machines.
		#UMAC=$(echo "$MAC" | sed -e "s|:||g")
		#if ! grep "^\"$UMAC\"," $UNATTENDCSV; then
		#	echo "^\"$UMAC\",\"ComputerName\",\"$NOM\"" >> $UNATTENDCSV
		#	echo "^\"$NOM\",\"FullName\",\"$NOM\"" >> $UNATTENDCSV
		#fi
	
		t=$(ldapsearch -xLLL macAddress=$MAC)
		if [ -z "$t" ]; then
			echo "dn: cn=$NOM,ou=Computers,${BASEDN}
cn: $NOM
objectClass: top
objectClass: ipHost
objectClass: ieee802Device
objectClass: organizationalRole
ipHostNumber: $IP
macAddress: $MAC
" > ${tmp}/${NOM}.ldif
			echo "Ajout de $NOM"
			ldapadd -x -D ${ROOTDN} -w ${ADMINPW} -f ${tmp}/${NOM}.ldif
			if [ "$?" = "0" -a -n "$PARC" ]; then

				t=$(ldapsearch -xLLL -b ou=Parcs,$BASEDN cn=$PARC)
				if [ -z "$t" ]; then
					echo "Creation du parc $PARC et ajout de $NOM au parc"
					echo "dn: cn=$PARC,ou=Parcs,${BASEDN}
objectClass: groupOfNames
cn: $PARC
member: cn=$NOM,ou=Computers,$BASEDN
" > ${tmp}/${PARC}_avec_${NOM}.ldif
					ldapadd -x -D ${ROOTDN} -w ${ADMINPW} -f ${tmp}/${PARC}_avec_${NOM}.ldif
				else
					echo "Ajout de $NOM au parc $PARC"
					echo "dn: cn=$PARC,ou=Parcs,${BASEDN}
changetype: modify
add: member
member: cn=$NOM,ou=Computers,$BASEDN
" > ${tmp}/${PARC}_ajout_${NOM}.ldif
					ldapmodify -x -D ${ROOTDN} -w ${ADMINPW} -f ${tmp}/${PARC}_ajout_${NOM}.ldif
				fi
			fi
		fi
	else
		echo "Ligne
    $A
non conforme, donc non traitee."
	fi
done < $DHCPCSV

echo "Si des entrees ont ete ajoutees, pensez a relancer unattended-generate.sh"
