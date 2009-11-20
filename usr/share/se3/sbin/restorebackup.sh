#!/bin/bash
#
# Verifie le point de montage du peripherique de backup
# BPCMEDIA=1 --> disque USB
# BPCMEDIA=2 --> disque dur
# BPCMEDIA=3 --> NAS

if [ -e "/etc/backuppc/restore.lck" ]; then
	exit 0
fi

ps auxw | grep fsck | grep -v grep && exit 0;

if [ -e /var/www/se3/includes/config.inc.php ]
then
        dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "impossible d'acceder aux params mysql"
        exit 1
fi

BPCMEDIA=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='bpcmedia'"`
bck_user=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='bck_user'"`
USBDISK=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='usbdisk'"`
USBDEV=/dev/disk/by-id/$USBDISK
#
# Disque USB
#

if [ "$BPCMEDIA" = "1" ]; then

mount |grep "\/var\/lib\/backuppc" >/dev/null || MBPC=0
if [ "$MBPC" = "0" ]; then
	echo "Disque USB non mont&#233;."
	echo "D&#233;tection du disque, veuillez patienter..."
	/etc/init.d/backuppc stop > /dev/null
	sleep 1
	ps auxw |grep backuppc | grep $bck_user | grep -v grep >/dev/null && killall -9 "/usr/bin/perl"
	if [ ! -z "$USBDEV" ]; then
		echo "Disque $USBDEV d&#233;tect&#233;"
		sleep 1
		mount ${USBDEV}-part1 /var/lib/backuppc
		if [ "$?" != "0" ]; then
			fsck.ext3 -y ${USBDEV}-part1
			mount ${USBDEV}-part1 /var/lib/backuppc
		fi
	fi
fi
mount |grep "\/var\/lib\/backuppc" >/dev/null || EXIT1=1
touch /var/lib/backuppc/test 2&> /dev/null || TBPC=0
if [ "$TBPC" = "0" ]; then
	echo "Impossible d'&#233;crire sur le disque USB"
	/etc/init.d/backuppc stop > /dev/null
	sleep 1
	ps auxw |grep backuppc | grep $bck_user | grep -v grep >/dev/null && killall -9 "/usr/bin/perl"
	umount /var/lib/backuppc
	echo "D&#233;tection du disque, veuillez patienter..."
	if [ ! -z "$USBDEV" ]; then
		echo "Disque $USBDEV d&#233;tect&#233;"
		sleep 1
		mount ${USBDEV}-part1 /var/lib/backuppc
		if [ "$?" != "0" ]; then
			fsck.ext3 -y ${USBDEV}-part1
			mount ${USBDEV}-part1 /var/lib/backuppc
		fi
	fi
fi
mount |grep "\/var\/lib\/backuppc" >/dev/null || EXIT1=1
touch /var/lib/backuppc/test 2&> /dev/null || EXIT1=1
if [ "$EXIT1" = 1 ]; then
	echo "Impossible de remettre en route le disque USB" 
	echo "Impossible de remettre en route le disque USB" | mail -s "Erreur sauvegarde se3" root
	/etc/init.d/backuppc stop > /dev/null
	sleep 5
	ps auxw |grep backuppc | grep $bck_user | grep -v grep >/dev/null && killall -9 "/usr/bin/perl"
	exit 1
fi

fi

#
# NAS
#

if [ "$BPCMEDIA" = "3" ]; then
mount |grep "\/var\/lib\/backuppc" >/dev/null || MBPC=0
if [ "$MBPC" = "0" ]; then
	echo "NAS non mont&#233;."
	# Recuperation des parametres
	NAS_protocol=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_protocol'"`
	NAS_ip=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_ip'"`
	NAS_share=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_share'"`
	NAS_login=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_login'"`
	NAS_pass=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_pass'"`
	NAS_mntsuffix=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='NAS_mntsuffix'"`
	BPCUIDN=`mysql se3db -u $dbuser -p$dbpass -B -N -e "select value from params where name='bck_uidnumber'"`
	# BPCUIDN=`getent passwd www-se3| cut -d : -f3`

	if [ ! -d /var/lib/backuppc/$NAS_mntsuffix ]; then
		mkdir -p /var/lib/backuppc/$NAS_mntsuffix
	fi

	if [ "$NAS_protocol" = "cifs" ]; then
	# Montage du NAS en CIFS
		mount.cifs //$NAS_ip/$NAS_share /var/lib/backuppc/$NAS_mntsuffix -o username=$NAS_login,password=$NAS_pass,ip=$NAS_ip,uid=$BPCUIDN
		#echo "mount.cifs //$NAS_ip/$NAS_share /var/lib/backuppc/$NAS_mntsuffix -o username=$NAS_login,password=$NAS_pass,ip=$NAS_ip,uid=$BPCUIDN"
		sleep 3
	elif [ "$NAS_protocol" = "nfs" ]; then
	# Montage du NAS en NFS
		mount -t nfs $NAS_ip:$NAS_share /var/lib/backuppc/$NAS_mntsuffix
		#echo "mount -t nfs $NAS_ip:$NAS_share /var/lib/backuppc/$NAS_mntsuffix"
		sleep 3
		mount |grep "type nfs" | grep backuppc || (mount -t nfs4 $NAS_ip:$NAS_share /var/lib/backuppc/$NAS_mntsuffix ; sleep 3)
	fi
fi 

mount |grep "\/var\/lib\/backuppc" >/dev/null || EXIT1=1
touch /var/lib/backuppc/$NAS_mntsuffix/test 2&> /dev/null || TBPC=0
if [ "$TBPC" = "0" ]; then
	echo "Impossible d'&#233;crire sur le partage NAS"
	/etc/init.d/backuppc stop > /dev/null
	sleep 5
	ps auxw |grep backuppc | grep $bck_user | grep -v grep >/dev/null && killall -9 "/usr/bin/perl"
	#umount /var/lib/backuppc/$NAS_mntsuffix
	exit 1
fi
if [ "$EXIT1" = 1 ]; then
	echo "Impossible de remettre en route le partage NAS"
	echo "Impossible de remettre en route le partage NAS" | mail -s "Erreur sauvegarde se3" root
	/etc/init.d/backuppc stop > /dev/null
	sleep 5
	ps auxw |grep backuppc | grep $bck_user | grep -v grep >/dev/null && killall -9 "/usr/bin/perl"
	exit 1
fi

fi

[ ! -d /var/lib/backuppc/pc ] && mkdir /var/lib/backuppc/pc
[ ! -d /var/lib/backuppc/cpool ] && mkdir /var/lib/backuppc/cpool

#
# Demarrage de backuppc
#
/etc/init.d/backuppc restart || (
    /usr/share/se3/sbin/droit_backuppc.sh
    /etc/init.d/backuppc start
)
