#!/bin/bash
# Run and Kill share's services defined in /usr/share/se3/shares/%NAME%
# Works like Sys V init

## $Id$ ##


RC_SHARE=/usr/share/se3/shares/shares.d
VERBOSE=0
LOGON=0
LOGOUT=0

function usage {
	echo "usage: $0 -v [-s|-k] share user computer address arch"
	echo "       -s : call logon scripts for share "
	echo "       -k : call logout scripts for share "
	echo "       -v : Verbose "
	exit $1;
}

function rc_logon {

	ERROR=0
	for script in "$RC_SHARE/$1/$5/"S*
	do
		[ ! -x $script ] && continue
		if [ "$VERBOSE" == "1" ]
		then
			echo "Running: $(basename $script) $2 $3 $4 $5" >> /var/log/samba/debug.log
		fi
		$script $2 $3 $4 $5
		if (( $? != 0 ))
		then
			ERROR=1
		fi
	done
	return $ERROR
}

function rc_logout {
	ERROR=0
	for script in "$RC_SHARE/$1/$5"/K*
	do
		[ ! -x $script ] && continue
		if [ "$VERBOSE" == "1" ]
		then
			echo "Running: $(basename $script) $2 $3 $4 $5" >> /var/log/samba/debug.log
		fi
		$script $2 $3 $4 $5
		if (( $? != 0 ))
		then
			ERROR=1
		fi
	done
	return $ERROR

}

while getopts ":skv" cmd
do
	case $cmd in	
		s) LOGON=1 ;;
		k) LOGOUT=1 ;;
		v) VERBOSE=1 ;;
		?) echo "bad option!"
		   usage 1;;
	esac
done

shift $(($OPTIND-1))
if (( $# != 5 ))
then
	usage 1
fi

# filtrage 
user=$(echo "$2" | tr 'A-Z' 'a-z' | sed "s/[\$_]$//")
machine=$(echo "$3" | tr 'A-Z' 'a-z' |  sed "s/[\$_]$//")

# on teste si windows fait des siennes
if [ "$user" == "$machine" ]
then
       exit 0
elif [ "$user" == "adminse3" ]
then
       exit 0
fi      

# ok, on transmet les infos sans transformation
if [ "$LOGON" == "1" ]
then
        rc_logon $1 $2 $3 $4 $5
elif [  "$LOGOUT" == "1" ]
then
        rc_logout $1 $2 $3 $4 $5
fi
exit $?
