#!/bin/bash
## $Id$ ##
#
##### script remplaçant l'utilisation désormais interdite des  ";"" dans smb.conf#####
#
# Contribution de out, merci à lui :)
unset MAKEHOME CONNEXION DECONNEXION LOGONPL LOGONPY LCSCANSEE LANCEUR  LOGOUTPY

function usage {
	echo "usage: $0 -a -m -c -d -l -p -s -h -w %u %m %I %a"
	echo "       -a :  call lanceur_applications.sh %u %m %a %I"
	echo "       -m :  call mkhome.pl %u %m"
	echo "       -c :  call connexion.sh %u %m %I"
	echo "       -d :  call deconnexion.pl %u %m %I"
	echo "       -l :  call logonpl %u %m %a"
	echo "       -p :  call logonpy %u %m %a"
	echo "       -s :  call LcsCanSee.pl %m lcs"
	echo "       -w :  call logoutpy %u %m"
	echo "       -h :  show this help"
	echo ""
	echo "       ex.:  $0 -mcl %u %m %I %a"
	echo "             $0 -m -c %u %m %I %a"
	exit $1;
}

while getopts ":amcdlpshw" cmd
do
	case $cmd in	
            a) LANCEUR=1 ;;
			m) MAKEHOME=1 ;;
			c) CONNEXION=1 ;;
			d) DECONNEXION=1 ;;
			l) LOGONPL=1 ;;
			p) LOGONPY=1 ;;
			s) LCSCANSEE=1 ;;
			w) LOGOUTPY=1 ;;
			h) usage 0 ;;
			?) echo "bad option!"
			   usage 1 ;;
	esac
done
DEBUG=0
[ "$DEBUG" != "0" ] && echo "valeur de cmd : $cmd" >> /root/log_smb_rootexec.log 
shift $(($OPTIND-1))

if (( $# != 4 ))
then
	echo "bad arguments number"
	usage 1
fi

if [ $MAKEHOME ]
then
	[ "$DEBUG" != "0" ] && echo "/usr/share/se3/sbin/mkhome.pl $1 $2" >> /root/log_smb_rootexec.log 
	/usr/share/se3/sbin/mkhome.pl $1 $2
# inutile suite cgt sur logonpl
# 	chown admin:admins /home/netlogon/$1.bat
# 	chgrp admin:admins /home/netlogon/$1.txt
# 	chown 444 /home/netlogon/$1.bat
# 	chown 444 /home/netlogon/$1.txt
# 	
fi
if [ $CONNEXION ]
then
	[ "$DEBUG" != "0" ] && echo "/usr/share/se3/sbin/connexion.sh $1 $2 $3" >> /root/log_smb_rootexec.log 
	/usr/share/se3/sbin/connexion.sh $1 $2 $3
fi
if [ $LOGONPL ]
then
	[ "$DEBUG" != "0" ] && echo "/usr/share/se3/sbin/logonpl $1 $2 $4" >> /root/log_smb_rootexec.log 
	/usr/share/se3/sbin/logonpl $1 $2 $4
fi
if [ $LOGONPY ]
then
	[ "$DEBUG" != "0" ] && echo "/usr/share/se3/sbin/logonpy.sh $1 $2 $4" >> /root/log_smb_rootexec.log 
	/usr/share/se3/sbin/logonpy.sh $1 $2 $4
fi
if [ $DECONNEXION ]
then
	[ "$DEBUG" != "0" ] && echo "/usr/share/se3/sbin/deconnexion.pl $1 $2 $3" >> /root/log_smb_rootexec.log 
	/usr/share/se3/sbin/deconnexion.pl $1 $2 $3
fi

if [ $LCSCANSEE ]
then
	[ "$DEBUG" != "0" ] && echo "/usr/share/se3/sbin/LcsCanSee.pl $2 lcs" >> /root/log_smb_rootexec.log 
	/usr/share/se3/sbin/LcsCanSee.pl $2 lcs
fi

if [ $LANCEUR ]
then
	[ "$DEBUG" != "0" ] && echo "/usr/share/se3/sbin/lanceur_applications.sh $1 $2 $3 $4 $5" >> /root/log_smb_rootexec.log 
	/usr/share/se3/sbin/lanceur_applications.sh $1 $2 $3 $4
fi

if [ $LOGOUTPY ]
then
	[ "$DEBUG" != "0" ] && echo "/usr/share/se3/sbin/logoutpy $1 $2" >> /root/log_smb_rootexec.log 
	/usr/share/se3/sbin/logoutpy.sh $1 $2
fi

