#!/bin/bash

# $Id$
#
# Script de dedoublonnage des uidNumber

ladate=$(date +%Y%m%d%H%M%S)

dosstmp=/root/tmp/recherche_uidnumber_en_doublons_$(date +%Y%m%d)
mkdir -p $dosstmp
fichcsv=$dosstmp/doublons.csv

echo "Initialisation des variables BASEDN, ROOTDN et PASSDN"
t=$(ps aux|grep mysqld|grep "^mysql")
#echo "t=$t"
if [ -n "$t" ]; then
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

	BASE_DN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	if [ -z "$BASE_DN" ]; then
		echo "Impossible d'acceder au parametre BASE_DN"
		exit 1
	fi
	BASEDN=$BASE_DN
	ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	if [ -z "$ADMINRDN" ]; then
		echo "Impossible d'acceder au parametre ADMINRDN"
		exit 1
	fi
	#ROOTDN=$ADMINRDN
	ROOTDN=$ADMINRDN,$BASEDN
	ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	if [ -z "$ADMINPW" ]; then
		echo "Impossible d'acceder au parametre ADMINPW"
		exit 1
	fi
	PASSDN=$ADMINPW
	PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	if [ -z "$PEOPLERDN" ]; then
		echo "Impossible d'acceder au parametre PEOPLERDN"
		exit 1
	fi
	GROUPSRDN=`echo "SELECT value FROM params WHERE name='groupsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	if [ -z "$GROUPSRDN" ]; then
		echo "Impossible d'acceder au parametre GROUPSRDN"
		exit 1
	fi
	RIGHTSRDN=`echo "SELECT value FROM params WHERE name='rightsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	if [ -z "$RIGHTSRDN" ]; then
		echo "Impossible d'acceder au parametre RIGHTSRDN"
		exit 1
	fi
else
	echo "Le service MySQL ne semble pas accessible."
	echo "Utilisation des parametres locaux."
	echo "Ils ne sont valides que si le service LDAP tourne sur le SE3 lui-meme."

	BASEDN=$(cat /etc/ldap/ldap.conf | grep "^BASE" | tr "\t" " " | sed -e "s/ \{2,\}/ /g" | cut -d" " -f2)
	ROOTDN=$(cat /etc/ldap/slapd.conf | grep "^rootdn" | tr "\t" " " | cut -d'"' -f2)
	PASSDN=$(cat /etc/ldap.secret)
	PEOPLERDN="ou=People"
	GROUPSRDN="ou=Groups"
	RIGHTSRDN="ou=People"
fi

GET_UIDNUMBER() {
	for i in $(seq 1000 4999)
	do
		t=$(ldapsearch -xLLL uidNumber=$i uidNumber)
		if [ -z "$t" ]; then
			echo $i
			#break
			exit 0
		fi
	done
	echo "-1"
}

if [ "$1" = "go" -a -e $fichcsv ]; then
	# Faire une sauvegarde de l'annuaire
	echo "Sauvegarde initiale de l'annuaire..."
	ldapsearch -xLLL -D "$ROOTDN" -w "$PASSDN" > $dosstmp/ldapsearch_${ladate}.ldif
	if [ "$?" != "0" ]; then
		echo "ERREUR lors de la sauvegarde."
		echo "Abandon par précaution."
		exit
	fi

	sambaSID=$(net getlocalsid|cut -d":" -f2|sed -e "s/ //g")

	uidnumprec=1
	while read A
	do
		uidnumber=$(echo "$A" | cut -d";" -f1)
		uid=$(echo "$A" | cut -d";" -f2)

		if [ "${uidnumber}" = "${uidnumprec}" ]; then
			uidnumnew=$(GET_UIDNUMBER)
			if [ "${uidnumnew}" != "-1" ]; then
				echo "Changement pour $uid: ${uidnumber} -> ${uidnumnew}"
				nouvelrid=$((2*${uidnumnew}+1000))
				echo "dn: uid=$uid,$PEOPLERDN,$BASEDN
changetype: modify
replace: uidNumber
uidNumber: ${uidnumnew}
-
replace: sambaSID
sambaSID: $sambaSID-$nouvelrid
" > $dosstmp/modif_$uid.ldif

				ldapmodify -x -D $ROOTDN -w $PASSDN -f $dosstmp/modif_$uid.ldif

				## Correction des proprios:
				#find /home/$uid -uid ${uidNumber} | while read B
				#do
				#	chown $uid "$B"
				#done
			else
				echo "Il n'y a plus d'uidNumber libre."
				exit
			fi
		fi
		uidnumprec=${uidnumber}
	done < $fichcsv

	echo "ATTENTION: Lancez maintenant le script restore_droits.sh pour corriger"
	echo "           les proprios."
else
	rm -f $fichcsv
	for i in $(seq 1000 5000)
	do
		j=$(ldapsearch -x uidNumber=$i uid |grep numEntries | cut -d: -f 2)
		if [ ! $j = 1 ]; then
			ldapsearch -xLLL uidNumber=$i uid | grep "^uid: " | sed "s/^uid: //" | while read uid
			do
				echo "$i;$uid" | tee -a $fichcsv
			done
		fi
	done
	echo "Vous pouvez relancer le script avec le paramètre 'go' pour changer l'uidNumber du doublon."
fi

