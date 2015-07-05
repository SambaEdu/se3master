#!/bin/bash

## $Id$ ##

# Update share preexec postexec scripts
SHARES=/usr/share/se3/shares/shares.d
AVAIL=/usr/share/se3/shares/shares.avail
SMB_CONF=smb_Win95.conf,smb_WinXP.conf,smb_Win2K.conf,smb_Vista.conf,smb_CIFSFS.conf,smb.conf
DEFAULT_ARCHS="Win95 WinXP Win2K Vista CIFSFS"
ARGS=0
VERBOSE=0
ADD=0
REMOVE=0
DEFAULTS=0
LIST=0
export VERBOSE


function usage {
	echo "usage: $0 [-v] -d [share]"
	echo "usage: $0 [-v] -a share script [arch1 arch2 ...]"
	echo "usage: $0 [-v] -r share script [arch1 arch2 ..."
	echo "usage: $0 [-v] -l"
	echo "       -d : Use defaults scripts"
	echo "       -a : Add script to share"
	echo "       -r : Remove script from share"
	echo "       -l : List available scripts"
	echo "       -v : Verbose"
	exit $1;
}

function clean_scripts
{
	rm -f $AVAIL/*~
}
function list_all_shares
{
	IFS=,
	(
	for file in $SMB_CONF
	do
		cat /etc/samba/"$file"|grep "\["|grep "\]"|grep -v global|while read share
		do
			expr "$share" : '\[\(.*\)\]'
		done
	done
	)|sort|uniq
	IFS=
}

function get_action {
	ACTION="$(expr "$(grep action: $AVAIL/$1)" : '.*:[ ]*\(.*\)')"
	if [ "$ACTION" == "start" ]
	then
		echo S
	elif [ "$ACTION" == "stop" ]
	then
		echo K
	fi
}

function get_level {
	LEVEL="$(expr "$(grep level: $AVAIL/$1)" : '.*:[ ]*\(.*\)')"
	if (( 10#$LEVEL>0 && 10#$LEVEL<100 ))
	then
		echo $LEVEL
	fi
}

# Return defaults script for share
# Output: script arch ...
function get_defaults_scripts {
	for file in "$AVAIL"/*
	do
		# foreach arch
		SCRIPT_VALID_FOR_SHARE=0
		grep -H  "shares_.*:" "$file"|cut -d ':' -f2|cut -d '_' -f2|( while read arch
		do
			# foreach share
			shares="$(grep -H "shares_$arch:" "$file"|cut -d ':' -f3)"
			IFS=,
			for share in $shares
			do
				if [ "$(echo $share|sed -s 's/ //g')" == "$1" ]
				then
					if (( SCRIPT_VALID_FOR_SHARE == 0 ))
					then
						echo -n "$(basename $file) "
						SCRIPT_VALID_FOR_SHARE=1
					fi
					echo -n "$arch "
				fi
			done
		done
		if (( SCRIPT_VALID_FOR_SHARE == 1 ))
		then
			echo
		fi
		)
	done
}

# Add $2 script for share $1 with arch $3
function share_add_script {
	SHARE="$1"
	SCRIPT="$2"
	ACTION="$(get_action $SCRIPT)"
	LEVEL="$(get_level $SCRIPT)"
	if [[ -z $ACTION || -z $LEVEL ]]
	then
		echo "Bad headers for $2: action=$ACTION, level=$LEVEL"
		exit 1
	fi
	shift 2
	for arch in $@
	do
		[ ! -d "$SHARES/$SHARE/$arch" ] && mkdir -p "$SHARES/$SHARE/$arch"
		if (( VERBOSE == 1 ))
		then
			echo "Add $SCRIPT to $SHARE share ($arch)..."
		fi
		ln -sf "$AVAIL/$SCRIPT" "$SHARES/$SHARE/$arch/$ACTION$LEVEL$SCRIPT"
	done
}

function share_remove_script {
	SHARE="$1"
        SCRIPT="$2"
	shift 2
	for arch in $@
	do
		if (( VERBOSE == 1 ))
		then
			echo "Remove $SCRIPT from $SHARE share ($arch)..."
		fi
		rm -f "$SHARES/$SHARE/$arch"/[KS]*"$SCRIPT"
	done
}

function share_defaults_scripts {
	rm -rf "$SHARES/$1"/*
	(( VERBOSE == 1)) && echo -n "Add to share $1: "
	get_defaults_scripts $1|while read script archs
	do
		(( VERBOSE == 1 )) && echo
		(( VERBOSE == 1 )) && echo -n "      - $script ("
		for arch in $archs
		do
			(( VERBOSE == 1 )) && echo -n "$arch, "
			VERBOSE=0 share_add_script $1 $script $arch $2
		done
		(( VERBOSE == 1 )) && echo -n -e "\b\b)"
	done
	(( VERBOSE == 1 )) && echo
}

function scripts_list {
	for script in "$AVAIL"/*
	do
		basename "$script"
	done
}

while getopts ":valrd" cmd
do
	case $cmd in	
		a) ADD=1
		   ARGS=2
		   break;;
		r) REMOVE=1
		   ARGS=2
		   break;;
		d) DEFAULTS=1 
		   ARGS=0
		   break;;
		l) LIST=1 
		   ARGS=0
		   break;;
		v) VERBOSE=1 ;;
		?) echo "bad option!"
		   usage 1 ;;
	esac
done

shift $(($OPTIND-1))

if (( ARGS > $# ))
then
	usage 1
fi

clean_scripts

if (( ADD == 1 ))
then
	if (( $# > 2 ))
	then
		share_add_script $@
	else
		share_add_script $1 $2 $DEFAULT_ARCHS
	fi
elif (( REMOVE == 1 ))
then
	if (( $# > 2 ))
	then
		share_remove_script $@
	else
		share_remove_script $1 $2 $DEFAULT_ARCHS
	fi
elif (( DEFAULTS == 1 ))
then	
	if (( $# > 0 ))
	then	
		share_defaults_scripts $1
	else
		list_all_shares|while read share
		do
			share_defaults_scripts $share
		done
	fi
elif (( LIST == 1 ))
then
	scripts_list
else 
	usage 1
fi
