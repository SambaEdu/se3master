#!/bin/bash
# moulinage des groupOfnames en posix
# Olivier lecluse
# 27/04/2007

# recuperation des poarams bdd

WWWPATH="/var/www"
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
        dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "Fichier de conf inaccessible."
	exit 1
fi

# Recuperation des params ldap

LDAPIP=`echo "SELECT value FROM params WHERE name='ldap_server'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$LDAPIP" ]; then
        echo "Impossible d'acc?der au param?tre BASEDN"
        exit 1
fi
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
        echo "Impossible d'acc?der au param?tre BASEDN"
        exit 1
fi
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINRDN" ]; then
        echo "Impossible d'acc?der au param?tre ADMINRDN"
        exit 1
fi
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINPW" ]; then
        echo "Impossible d'acc?der au param?tre ADMINPW"
        exit 1
fi
PEOPLERDN=`echo "SELECT value FROM params WHERE name='peopleRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$PEOPLERDN" ]; then
        echo "Impossible d'acc?der au param?tre PEOPLERDN"
        exit 1
fi
GROUPSRDN=`echo "SELECT value FROM params WHERE name='groupsRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$GROUPSRDN" ]; then
        echo "Impossible d'acc?der au param?tre GROUPSRDN"
        exit 1
fi
PEOPLER=`echo $PEOPLERDN |cut -d = -f 2`
GROUPSR=`echo $GROUPSRDN |cut -d = -f 2`

# Sauvegarde de l'annuaire avant moulinage

ldapsearch -xLLL -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW objectClass=* > gonbeforeposix.ldif

# Moulinage des gon dans un fichier ldif

echo "">/tmp/addposix.ldif
GIDN=`ldapsearch -xLLL objectClass=posixGroup gidNumber | grep gidNumber | cut -d" " -f 2 | sort -n | tail -n 10 | head -n 1`

ldapsearch -xLLL -h $LDAPIP -b $GROUPSRDN,$BASEDN objectClass=groupOfNames  | grep "^dn:"  | cut -c 5- | cut -d"," -f1 | while read GRDN; do
	GDN="$GRDN,$GROUPSRDN,$BASEDN"
	echo "dn: $GDN">>/tmp/addposix.ldif
	ldapsearch -xLLL -h $LDAPIP $GRDN | grep -v "^member" |grep -v "^dn:" | sed -e "s/groupOfNames/posixGroup/g" | sed -e "s/,$PEOPLERDN,$BASEDN//g" |grep ":" >> /tmp/addposix.ldif
	# recherche d'un gidNumber libre...
	while getent group $GIDN; do
		let GIDN+=1
	done
	echo "gidNumber: $GIDN">>/tmp/addposix.ldif
	# Moulinage des member en memberUid
	ldapsearch -xLLL -h $LDAPIP $GRDN  | grep "^member:" |sed -e "s/member: uid=/memberUid: /g" | grep -v "member:"  | cut -d"," -f 1 >> /tmp/addposix.ldif

	echo "">>/tmp/addposix.ldif
	echo "=======> groupe : $GRDN ($GIDN)"
	let GIDN+=1
	ldapdelete -x -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW $GDN
done

# Integration des groupes posix

ldapadd -x -c -h $LDAPIP -D $ADMINRDN,$BASEDN -w $ADMINPW -f /tmp/addposix.ldif
