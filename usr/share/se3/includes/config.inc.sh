#!/bin/bash

## $Id: config.inc.sh 4852 2009-11-17 16:27:29Z keyser $ ##
#
##### script permettant la lecture des infos dans la table params de mysql #####
#


# unset CONFIG LDAP PATHSE3 BACKUP SYSTEM HIDE VERSBOSE
function usage {
	echo "script permettant la lecture des infos dans la table params de mysql" 
	echo "usage: $0 -c -l -p -b -h -s -m -d -o"
	echo "       -c :  parametres de configuration generale, ex urlse3"
	echo "       -l :  parametres ldap, ex ldap_base_dn"
	echo "       -p :  chemins, ex path_to_wwwse3"
	echo "       -b :  parametres sauvegarde, ex bck_user"
	echo "       -m :  parametres masques, ex xppass"
	echo "       -s :  parametres systemes, ex quota_warn_home "
	echo "       -d :  parametres dhcp, ex dhcp_iface"
	echo "       -o :  only : uniquement les variables pour interoger mysql"
	echo "       -h :  show this help"
	echo "       -v :  mode verbeux : liste les variables initialisees"
	exit $1
}


function getmypasswd {

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
}

function getmysql {
getmypasswd
for i in $(echo "SELECT name FROM params WHERE cat='$1'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass | grep -v "^name$")
do
    eval $i="$(echo "SELECT value FROM params WHERE name='$i' " | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N | sed -e "s/[()]//g"|sed -e "s/ /_/g")"
    if [ "$2" == "1" ]; then
      echo "$i --> ${!i}"
    fi
done
}

function setmysql {
# set se3db param

getmypasswd
echo "insert into params set name='$2',value='$3',descr='$4',cat='$1';" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
}


if [ $# -eq "0" ]  # Script appele sans argument?
then
  echo "option incorrecte"
  usage 1 
fi

VERSBOSE=0
while getopts ":clpbmsdvho" cmd
do
	case $cmd in	
	c) CONFIG=1 ;;
	l) LDAP=1 ;;
	p) PATHSE3=1 ;;
	b) BACKUP=1 ;;
	m) HIDE=1 ;;
	s) SYSTEM=1 ;;
	d) DHCP=1 ;;
	o|v) VERSBOSE=1 ;;
	h) usage 0 ;;
	w) WRITE=1 ;;
	\?) echo "bad option!"
	usage 1 ;;
	*) echo "bad option!"
	usage 1 ;;
	esac
done

if [ "$WRITE" == "1" ]
then
    nom="$3"
    valeur="$4"
    descr=$*
    if [ "$CONFIG" == "1" ]; then
    setmysql "1" "$nom" "$valeur" "$descr" 
    fi

    if  [ "$LDAP" == "1" ]; then
    setmysql "2" "$nom" "$valeur" "$descr" 
    fi

    if [ "$PATHSE3" == "1" ]; then 
    setmysql "3" "$nom" "$valeur" "$descr" 
    fi

    if [ "$BACKUP" == "1" ]; then 
    setmysql "5" "$nom" "$valeur" "$descr" 
    fi

    if [ "$HIDE" == "1" ]; then
    setmysql "4" "$nom" "$valeur" "$descr" 
    fi

    if [ "$SYSTEM" == "1" ]; then
    setmysql "6" "$nom" "$valeur" "$descr" 
    fi

    if [ "$DHCP" == "1" ]; then
    setmysql "7" "$nom" "$valeur" "$descr" 
    fi
else

    if [ "$VERSBOSE" == "1" ]; then
      getmypasswd
      echo "dbhost --> ${dbhost}"
      echo "dbname --> ${dbname}"
      echo "dbuser --> ${dbuser}"
      echo "dbpass --> ${dbpass}"
    fi

    if [ "$CONFIG" == "1" ]; then
    getmysql "1" $VERSBOSE
    fi

    if  [ "$LDAP" == "1" ]; then
    getmysql "2" $VERSBOSE
    fi

    if [ "$PATHSE3" == "1" ]; then 
    getmysql "3" $VERSBOSE
    fi

    if [ "$BACKUP" == "1" ]; then 
    getmysql "5" $VERSBOSE
    fi

    if [ "$HIDE" == "1" ]; then
    getmysql "4" $VERSBOSE
    fi

    if [ "$SYSTEM" == "1" ]; then
    getmysql "6" $VERSBOSE
    fi

    if [ "$DHCP" == "1" ]; then
    getmysql "7" $VERSBOSE
    fi

fi
