#!/bin/bash
# Version 0.20
# program smartctl is using a deprecated SCSI ioctl, please convert it to SG_IO

##### Test l'état des disques #####


## $Id$ ##


if [ ! -z "$1" ]
then
	echo "Script permettant tester l'état des disques de la machine"
	echo "Usage : Aucune option"
	exit
fi	


#test smart existe
dpkg -l smartmontools > /dev/null
if [ $? == 1  ] 
	then
	apt-get install smartmontools
fi

# Nombre et type de disque
# Commande à modifier
#NBD=`dmesg |grep  Attached |wc |cut -d" " -f3-7`
NBD=`sfdisk -g | wc -l`

TYPED=`dmesg |grep -m 1 Attached |cut -d" " -f2`


# Controlleur disque
# 0=IDE   1=SCSI  2=RAID  3=Raid 3Ware
CTRL=1  #SCSI Par défaut
SCSI=` grep -B 2 Direct-Access /proc/scsi/scsi|grep Vendor |cut -d" " -f4`
#Test 3ware
if [ "$SCSI" == "3ware" ] # Controlleur Raid 3Ware
	then CTRL=3
	echo "Controlleur Raid Ide $SCSI"
elif [ "$SCSI" == "MegaRAID" ] # Controlleur Raid : Dans l'os, c'est mort pour smart
	then CTRL=2
	echo "Controlleur Raid SCSI $SCSI"
fi
#Test IDE
for I in a b c d e f g h i j k l m n 
	do
	EXIST=`ls /proc/ide/* |grep hd`
		if [ "$EXIST" != "" ]
		then
		DISK=`cat /proc/ide/hd$I/media 2>&1`
			if [ "$DISK" == "disk" ]
			then
			CTRL=0
			echo  "Controlleur IDE"
			RES=`smartctl -H  /dev/hd$I | grep "SMART overall-health"`
			PASS=`smartctl -H  /dev/hd$I |grep "SMART overall-health"|cut -d" " -f6-8`
				if [ "$PASS" == "PASSED"  ]
				then 
				echo "Disque N° $I (/dev/hd$I) sur controlleur IDE : OK"
				else
				echo "Disque N° $I (/dev/hd$I) sur controlleur IDE : Probleme"
				fi												
			fi
		fi
	done



if [ $NBD != 0 ]
	then
	echo "Vous avez $NBD disque(s) $TYPED dans cette machine"
	else
	echo "Problème lors de la détection des disques"
fi


if [ $CTRL == 3 ]
then
	for (( I=0 ; I<=12 ; I++ ))
	do
	RES=`smartctl -H -d 3ware,$I /dev/sda`
	EXIST=`smartctl -H -d 3ware,$I /dev/sda | grep overall-health`
	PASS=`smartctl -H -d 3ware,0 /dev/sda |grep overall-health|cut -d" " -f6`
		if [ "$EXIST" != "" ]
		then
			if [ "$PASS" == "PASSED"  ]
			then
			echo "Disque N° $I sur controlleur Raid 3ware : OK"
			else
			echo "Disque N° $I sur controlleur Raid 3ware : Probleme"
			fi
		fi
	done
fi


if [ $CTRL == 1 ]
then
	for I in a b c d e f g h i j k l m n 
	do
	RES=`smartctl -H  /dev/sd$I | grep "SMART Health Status"`
	PASS=`smartctl -H  /dev/sd$I |grep "SMART Health Status"|cut -d" " -f4`
		if [ "$RES" != "" ]
		then
			if [ "$PASS" == "OK"  ]
			then
			echo "Disque N° $I (/dev/sd$I) sur controlleur SCSI : OK"
			else
			echo "Disque N° $I (/dev/sd$I) sur controlleur SCSI : Probleme"
			fi
		fi
	done
fi

if [ $CTRL == 110 ]
then
	for I in a b c d e f g h i j k l m n 
		do
		DISK=`cat /proc/ide/hd$I/media|grep disk >/dev/null`
			if [ "$DISK" == "disk" ]
			then
			RES=`smartctl -H  /dev/hd$I | grep "SMART overall-health"`
			PASS=`smartctl -H  /dev/hd$I |grep "SMART overall-health"|cut -d" " -f6-8`
			echo "Disque N° $I (/dev/hd$I) sur controlleur IDE : OK"
			else
			echo "Disque N° $I (/dev/hd$I) sur controlleur IDE : Probleme"
		fi
	done
fi

