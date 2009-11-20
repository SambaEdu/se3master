#!/bin/bash
#
##### Script generant les scripts pour configurer un client SE3#####
#
# Auteur : Mickael POIRAULT Mickael.Poirault@ac-poitiers.fr
#
## $Id$ ##

if [ "$1" == "--help" -o "$1" == "-h" ]
then
        echo "Permet de générer les scripts pour configurer un client SE3"
	echo "Ubuntu 6.xx - 7.x - 8.x - Sarge"
        echo "Une fois générés les scripts sont placés dans le répertoire /root/"
        echo "Usage :    ./create_client.sh"
        echo "        Pour permettre à tous les comptes autorisés à accéder en root à se3"
		echo "        d'accéder aussi aux clients linux:"
        echo "           ./create_client.sh ssh_full"

	echo "Ce script est distribué selon les termes de la licence GPL"
        echo "--help cette aide"

        exit
fi

# recuperation des donnees utiles a la generation des scripts

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
        echo "Impossible d'accéder au paramètre BASE_DN"
        exit 1
fi

NTPSERVERS=`echo "SELECT value FROM params WHERE name='ntpserv'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
LDAP_SERVER=`echo "SELECT value FROM params WHERE name='ldap_server'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SE3_SERVER=`echo $HOSTNAME`

if [ -z "$LDAP_SERVER" ]; then
        echo "Impossible d'accéder au paramètre LDAP_SERVER"
        exit 1
fi

PASSADM=`echo "SELECT value FROM params WHERE name='xppass'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
TEST=$(dpkg -l|grep "ii  makepasswd ")
if [ -z "$TEST" ]; then
	apt-get install makepasswd
fi

PASSADMCRYPT=""
if [ -e "/usr/bin/makepasswd" -a -n "$PASSADM" ]; then
	PASSADMCRYPT=$(echo "$PASSADM" | makepasswd --clearfrom=- --crypt-md5 |awk '{ print $2 }')
	#PASSADMCRYPT=$(echo "$PASSADM" | makepasswd --clearfrom=- --crypt-md5 |awk '{ print $2 }'|tr "$" "#")
fi
# Debug:
#echo "PASSADM=$PASSADM"
#echo "PASSADMCRYPT=$PASSADMCRYPT"

SE3_IP=`cat /etc/network/interfaces | grep address | cut -d" " -f2`
NTPOPTIONS=`cat /etc/default/ntpdate | grep -v "#NTPOPTIONS" | grep "NTPOPTIONS" | sed 's/\"/\\\"/g'| sed 's/\"/\\\\"/g'`


if `cat /etc/samba/smb.conf | grep -v "#" | grep "ISO8859-15" >/dev/null`
then
	IOCHARSET="iso8859-15"
else
	if `cat /etc/samba/smb.conf | grep -v "#" | grep "UTF-8" >/dev/null`
	then
		IOCHARSET="utf8"
	else
		echo "Impossible de déterminer le jeu de caractères utilisé par samba"
		echo "Par défaut la valeur utilisée sera iso8859-15"
		IOCHARSET="iso8859-15"
	fi
fi

# Test la presence de la cle publique, et la copie dans /var/www/se3
if [ -e "/root/.ssh/authorized_keys" -a -n "$(echo $*|grep ssh_full)" ]
then
        cp /root/.ssh/authorized_keys /var/www/se3/authorized_keys
		if [ -e "/root/.ssh/id_rsa.pub" ]
		then
			cat /root/.ssh/id_rsa.pub >> /var/www/se3/authorized_keys
		fi
        chown www-se3 /var/www/se3/authorized_keys
        chmod 400 /var/www/se3/authorized_keys
else
	if [ -e "/root/.ssh/id_rsa.pub" ]
	then
			cp /root/.ssh/id_rsa.pub /var/www/se3/authorized_keys
			chown www-se3 /var/www/se3/authorized_keys
			chmod 400 /var/www/se3/authorized_keys
	fi
fi



# Cas ou LDAP_SERVEUR = 127.0.0.1
if [ "$LDAP_SERVER" = "127.0.0.1" ]
then
	LDAP_SERVER="$SE3_IP"
fi

# Test TLS
TLS=`grep TLS /etc/ldap/slapd.conf > /dev/null && echo 1`

# On deplace  les scripts dans /root
REJOINT_SE3_EXIST="0"
if [ -e "/var/cache/se3_install/client_linux/rejoint_se3_debian_sarge.sh" ]
then
        cp /var/cache/se3_install/client_linux/rejoint_se3_debian_sarge.sh /root/rejoint_se3_debian_sarge.sh
        REJOINT_SE3_EXIST=1
fi

if [ -e "/var/cache/se3_install/client_linux/rejoint_se3_ubuntu-6.sh" ]
then
        cp /var/cache/se3_install/client_linux/rejoint_se3_ubuntu-6.sh /root/rejoint_se3_ubuntu-6.sh
        REJOINT_SE3_EXIST=1
fi

if [ -e "/var/cache/se3_install/client_linux/rejoint_se3_edubuntu-6.10_7.04.sh" ]
then
        cp /var/cache/se3_install/client_linux/rejoint_se3_edubuntu-6.10_7.04.sh /root/rejoint_se3_edubuntu-6.10_7.04.sh
        REJOINT_SE3_EXIST=1
fi

if [ -e "/var/cache/se3_install/client_linux/rejoint_se3_ubuntu-7.10.sh" ]
then
        cp /var/cache/se3_install/client_linux/rejoint_se3_ubuntu-7.10.sh /root/
        REJOINT_SE3_EXIST=1
fi

if [ -e "/var/cache/se3_install/client_linux/rejoint_se3_ubuntu-8.04.sh" ]
then
        cp /var/cache/se3_install/client_linux/rejoint_se3_ubuntu-8.04.sh /root/
        REJOINT_SE3_EXIST=1
fi

# Modifie les scripts
if [ "$REJOINT_SE3_EXIST" = "1" ]
then
	perl -pi -e "s/###BASE_DN###/$BASE_DN/" /root/rejoint_se3_*.sh
	perl -pi -e "s/###LDAP_SERVER###/$LDAP_SERVER/" /root/rejoint_se3_*.sh
	perl -pi -e "s/###SE3_IP###/$SE3_IP/" /root/rejoint_se3_*.sh
	perl -pi -e "s/###SE3_SERVER###/$SE3_SERVER/" /root/rejoint_se3_*.sh
	perl -pi -e "s/###NTPSERVERS###/$NTPSERVERS/" /root/rejoint_se3_*.sh
	perl -pi -e "s/###NTPOPTIONS###/$NTPOPTIONS/" /root/rejoint_se3_*.sh
	perl -pi -e "s/###IOCHARSET###/$IOCHARSET/" /root/rejoint_se3_*.sh
	if [ -n "$PASSADMCRYPT" ]; then
		#perl -pi -e "s|###PASSADMCRYPT###|$(echo $PASSADMCRYPT |tr '#' '$')|" /root/rejoint_se3_*.sh
		#perl -pi -e "s|###PASSADMCRYPT###|$PASSADMCRYPT|" /root/rejoint_se3_*.sh
		sed -i "s|###PASSADMCRYPT###|$PASSADMCRYPT|" /root/rejoint_se3_*.sh
		# Debug:
		#grep "^PASSADMCRYPT=" /root/rejoint_se3_*.sh
	fi

	if [ "$TLS" = "1" ]
	then
		perl -pi -e "s/###TLS###/$TLS/" /root/rejoint_se3_*.sh
	fi

	chmod +x /root/rejoint_se3_*.sh
fi

# création du fichier smb_CIFSFS.conf dans le repertoire /etc/samba/

OK_smb_CIFSFS=`ls /etc/samba/smb_CIFSFS.conf 2>/dev/null`
if [ "$OK_smb_CIFSFS" == "" ]
then
	touch /etc/samba/smb_CIFSFS.conf
	echo "
	mangled names = false
	unix extensions = yes

[netlogon]
	comment = NetLogon
	path = /home/netlogon
	browseable = No
	locking = No
	root preexec = /usr/share/se3/sbin/logonpl %u %m %a

[homes]
	comment = Repertoire prive de %u sur %h
	path = /home/%u
	read only = No
	root preexec = /usr/share/se3/sbin/smb_rootexec.sh -mc %u %m %I %a
	map archive = no
	case sensitive = yes
	delete readonly = yes
	mangled names = false
	root postexec = /usr/share/se3/sbin/deconnexion.pl %u %m %I" > /etc/samba/smb_CIFSFS.conf
	#/etc/init.d/samba restart
else
TST=$(cat /etc/samba/smb_CIFSFS.conf| grep "deconnexion.pl")
[ -z "$TST" ] && echo "root postexec = /usr/share/se3/sbin/deconnexion.pl %u %m %I" >> /etc/samba/smb_CIFSFS.conf
fi
TST2=$(cat /etc/samba/smb.conf| grep "include = /etc/samba/smb_%a.conf")
if [ -z "$TST2" ]; then
	echo "votre fichier smb.conf ne semble pas comporter la ligne include = /etc/samba/smb_%a.conf" >&2
	echo "cette ligne est nécessaire au bon fonctionnement des clients linux" >&2
	echo "Sans doute votre fichier de configuration de samba n'est il pas modulaire" >&2
	echo "Vous devrez donc l'éditer si vous souhaitez activer les clients linux" >&2
	exit 1
fi
echo -e "\033[1;31m"
if [ "$REJOINT_SE3_EXIST" = "0" ]
then
     echo "Attention les scripts rejoint_se3_sarge et rejoint_se3_ubuntu ne semblent pas exister"
else
    echo "Le script à exécuter sur les clients a été placé dans /root"
    echo "Connectez vous depuis le client Linux avec la commande scp :"
    echo -e "\033[1;37m"
    echo "scp root@$SE3_IP:/root/rejoint_se3*.sh ."
    echo -e "\033[1;31m"
    echo "Changer les droits pour permettre son execution :"
    echo -e "\033[1;37m"
    echo "chmod +x rejoint_se3*.sh"
    echo -e "\033[1;31m"
    echo "puis lancez le."
fi
echo -e "\033[1;37m"

