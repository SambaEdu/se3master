#!/bin/bash

## $Id$ ##
#
##### script permettant la lecture des infos dans la table params de mysql #####
#


# unset CONFIG LDAP PATHSE3 BACKUP SYSTEM HIDE VERSBOSE
function usage {
	echo "script permettant la lecture des infos dans la table params de mysql" 
	echo "usage: $0 -c -l -p -b -h -s -m -d -o -f"
	echo "       -c :  parametres de configuration generale, ex urlse3"
	echo "       -l :  parametres ldap, ex ldap_base_dn"
	echo "       -p :  chemins, ex path_to_wwwse3"
	echo "       -b :  parametres sauvegarde, ex bck_user"
	echo "       -m :  parametres masques, ex xppass"
	echo "       -s :  parametres systemes, ex quota_warn_home "
	echo "       -d :  parametres dhcp, ex dhcp_iface"
	echo "       -o :  only : uniquement les variables pour interroger mysql"
	echo "       -h :  show this help"
	echo "       -v :  mode verbeux : liste les variables initialisees"
	echo "       -f :  ecrit les parametres selectionnes dans les ficihers cache /etc/se3/config_*.cache.sh "
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
if [ "$2" == "2" ]; then
	echo "# parametres se3, ne pas modifier" > $3
fi

for i in $(echo "SELECT name FROM params WHERE cat='$1'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass | grep -v "^name$")
do
    eval $i="$(echo "SELECT value FROM params WHERE name='$i' " | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N | sed -e "s/[()]//g"|sed -e "s/ /_/g")"
    if [ "$2" == "1" ]; then
        echo "$i-->${!i}"
    elif [ "$2" == "2" ]; then
        echo "$i=\"${!i}\"" >> $3
    fi
done
if [ "$2" == "2" ]; then
	chmod 700 $3
fi

}


if [ $# -eq "0" ]  # Script appele sans argument?
then
  echo "option incorrecte"
  usage 1 
fi

VERSBOSE=0
while getopts ":clpbmsdvhof" cmd
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
	f) VERSBOSE=2 ;;
	h) usage 0 ;;
	\?) echo "bad option!"
	usage 1 ;;
	*) echo "bad option!"
	usage 1 ;;
	esac
	fichier=/etc/se3/config_$cmd.cache.sh
done

if [ "$VERSBOSE" == "1" ]; then
    getmypasswd
    echo "dbhost-->${dbhost}"
    echo "dbname-->${dbname}"
    echo "dbuser-->${dbuser}"
    echo "dbpass-->${dbpass}"
elif [ "$VERSBOSE" == "2" ]; then
    getmypasswd
    fichier=/etc/se3/config_o.cache.sh
    echo "dbhost=\"${dbhost}\"" > $fichier
    echo "dbname=\"${dbname}\"" >> $fichier
    echo "dbuser=\"${dbuser}\"" >> $fichier
    echo "dbpass=\"${dbpass}\"" >> $fichier
    chmod 700 $fichier
    chown -R www-se3 /etc/se3
fi


proxy=$(grep "http_proxy=" /etc/profile | head -n 1 | sed -e "s#.*//##;s/\"//")

if [ ! -z "$proxy" ]; then
export http_proxy="http://$proxy"
export https_proxy="http://$proxy"
export ftp_proxy="http://$proxy"
fi


if [ "$CONFIG" == "1" ]; then
    getmysql "1" $VERSBOSE /etc/se3/config_c.cache.sh
    
fi

if  [ "$LDAP" == "1" ]; then
getmysql "2" $VERSBOSE /etc/se3/config_l.cache.sh
fi

if [ "$PATHSE3" == "1" ]; then 
getmysql "3" $VERSBOSE /etc/se3/config_p.cache.sh
fi

if [ "$BACKUP" == "1" ]; then 
getmysql "5" $VERSBOSE /etc/se3/config_b.cache.sh
fi

if [ "$HIDE" == "1" ]; then
getmysql "4" $VERSBOSE /etc/se3/config_m.cache.sh
fi

if [ "$SYSTEM" == "1" ]; then
getmysql "6" $VERSBOSE /etc/se3/config_s.cache.sh
fi

if [ "$DHCP" == "1" ]; then
getmysql "7" $VERSBOSE /etc/se3/config_d.cache.sh
fi

