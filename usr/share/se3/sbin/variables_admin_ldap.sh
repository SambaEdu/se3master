#!/bin/bash

# $Id$


# Script destine à disposer de variable d'acces au LDAP pour simplifier l'ecriture de commandes ldpasearch, ldapadd, ldapdelete, ldapmodify
# Auteur: Stephane Boireau
# Derniere modification: 13/05/2009

temoin=0
if [ "$0" != "-bash" -a "$0" != "/bin/bash" -a "$1" != "lib" ]; then
        temoin=1
fi

if [ "$temoin" = "1" -o "$1" = "-h" -o "$1" = "--help" ]; then
#if [ "$0" != "-bash" -o "$1" = "-h" -o "$1" = "--help" ]; then
	echo "Ce script permet d'initialiser des variables BASEDN, ROOTDN et PASSDN"
	echo "pour pouvoir ensuite effectuer des requètes sur le ldap."
	#echo "   ldapsearch -xLLL -D \$ROOTDN -w \$PASSDN uid=toto"
	#echo "   ldapdelete -xLLL -D \$ROOTDN -w \$PASSDN uid=toto,ou=People,\$BASEDN"
	#echo "   ..."
	echo ""
	echo "USAGE: Il faut appeler ce script par 'source $0'"
	echo "       Ou"
	echo "       dans un script:"
	echo "            . /usr/share/se3/sbin/variables_admin_ldap.sh lib > /dev/null"
	echo "       On dispose des variables BASEDN, ROOTDN et PASSDN pour le script dans"
	echo "       lequel on a fait cet appel."
else
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
fi

echo "Exemples d'usages: "
echo "   ldapsearch -xLLL -D \$ROOTDN -w \$PASSDN uid=toto"
echo "   ldapadd -x -D \$ROOTDN -w \$PASSDN -f fichier.ldif"
echo "   ldapmodify -x -D \$ROOTDN -w \$PASSDN -f fichier.ldif"
echo "   ldapdelete -x -D \$ROOTDN -w \$PASSDN uid=truc,\$PEOPLERDN,\$BASEDN"
