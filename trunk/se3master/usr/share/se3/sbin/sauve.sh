#!/bin/bash

# $Id$ #

##### Lance la sauvegarde sur bande #####

# D�tection de la distrib

if [ -e /etc/redhat-release ]; then
        DISTRIB="RH"
        WWWPATH="/var/www/html"
fi
if [ -e /etc/mandrake-release ]; then
        DISTRIB="MDK"
        WWWPATH="/var/www/html"
fi
if [ -e /etc/debian_version ]; then
        DISTRIB="DEB"
        WWWPATH="/var/www"
fi

SE3LOG="/var/log/se3/backup.log"
XFSLOG="/var/log/se3/xfsdump.log"
MEDIA="tape_"
XFSDUMP="/usr/sbin/xfsdump"
ERASE="-E"

# R�cup�ration des param�tres mysql

if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
        dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "Fichier de conf inaccessible" >> $SE3LOG
	echo "sauve.sh: Status FAILED" >> $SE3LOG
        exit 1
fi

# Test si la sauvegarde sur bande est active, sinon quitte
SAVBANDACTIV=`echo "SELECT value FROM params WHERE name='savbandactiv'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ "$SAVBANDACTIV" = "0" ]
then
	echo "Sauvegarde d�sactiv�e"
	exit 0
fi

MELSAVADMIN=`echo "SELECT value FROM params WHERE name='melsavadmin'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVLEVEL=`echo "SELECT value FROM params WHERE name='savlevel'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVBANDNBR=`echo "SELECT value FROM params WHERE name='savbandnbr'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVDEVICE=`echo "SELECT value FROM params WHERE name='savdevice'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVHOME=`echo "SELECT value FROM params WHERE name='savhome'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVSE3=`echo "SELECT value FROM params WHERE name='savse3'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVSUSPEND=`echo "SELECT value FROM params WHERE name='savsuspend'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

# V�rification de l'int�grit� des param�tres

echo "-----------------------------" >> $SE3LOG
date >> $SE3LOG
echo "-----------------------------" >> $SE3LOG

if [ -z "SAVDEVICE" ]; then
	echo "Le p�riph�rique de sauvegarde n'est pas renseign� ou l'acc�s � la base des param�tres est impossible. La sauvegarde a �chou�e." >> $SE3LOG
	echo "sauve.sh: Status FAILED" >> $SE3LOG
        exit 1
fi

if [ "$SAVSUSPEND" = "1" ]; then
	echo "La sauvegarde est suspendue..." >> $SE3LOG
	echo "sauve.sh: Status SUSPEND" >> $SE3LOG
	exit 0
fi

if [ ! "$SAVHOME" = "0" ]; then
	FLAGR=""
	if [ "$SAVHOME" = "2" ]; then
		FLAGR="-R"
	else
		# Commencement d'un nouveau cycle de sauvegarde, j'efface XFSLOG
		echo "" > $XFSLOG
	fi
	echo "---------------------------------------------------------" >>$XFSLOG
	SESSION="home_"
	echo "$XFSDUMP -F -l $SAVLEVEL -L $SESSION$SAVLEVEL $ERASE -M $MEDIA$SAVLEVEL$SAVBANDNBR $FLAGR -f $SAVDEVICE /home" >>$XFSLOG
	$XFSDUMP -F -l $SAVLEVEL -L $SESSION$SAVLEVEL $ERASE -M $MEDIA$SAVLEVEL$SAVBANDNBR $FLAGR -f $SAVDEVICE /home >> $XFSLOG
	STATUS=`tail -n 1 $XFSLOG | cut -d : -f 3 | sed -e "s/ //g"`
	echo "Sauvegarde de /home: $STATUS" >>$SE3LOG
	if [ "$STATUS" = "SUCCESS" ]; then
		echo "UPDATE params SET value=\"0\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		ERASE=""
		if [ "$SAVSE3" = "0" ]; then
			# La sauvegarde est achev�e avec succes
			if [ "$SAVBANDNBR" != "0" ]; then
				echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
			fi
			echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
			echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
			echo -e "Succ�s de la sauvegarde.\n Pensez � faire une rotation de la bande en cas de besoin." | mail -s "Sauvegarde SE3" $MELSAVADMIN
			echo "sauve.sh: La sauvegarde s'est termin�e avec succ�s" >> $SE3LOG
		fi
	fi
	if [ "$STATUS" = "INTERRUPT" ]; then
		echo "UPDATE params SET value=\"2\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		# Incr�mentation du compeur de bande
		let SAVBANDNBR+=1
		echo "UPDATE params SET value=\"$SAVBANDNBR\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo -e "Sauvegarde de /home inachev�e.\n Lorsque vous aurez ins�r� une nouvelle bande, �ditez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 0
	fi
	# Il y a des erreurs non r�cup�rables
	# Il faut recommencer le processus de savegarde entier /home et /var/se3 :-(
	# La sauvegarde est plac�e en �tat suspendu
	if [ "$STATUS" = "QUIT" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /home: Le m�dia n'est plus utilisable, changez de bande puis �ditez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "INCOMPLETE" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /home: La sauvegarde est incomplete. Editez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "FAULT" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /home: Erreur logicielle. Editez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "ERROR" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /home: Erreur ressource. Editez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
fi

if [ ! "$SAVSE3" = "0" ]; then
	FLAGR=""
	if [ "$SAVSE3" = "2" ]; then
		FLAGR="-R"
	fi
	echo "---------------------------------------------------------" >>$XFSLOG
	SESSION="se3_"
	echo "$XFSDUMP -F -l $SAVLEVEL -L $SESSION$SAVLEVEL $ERASE -M $MEDIA$SAVLEVEL$SAVBANDNBR $FLAGR -f $SAVDEVICE /var/se3" >>$XFSLOG
	$XFSDUMP -F -l $SAVLEVEL -L $SESSION$SAVLEVEL $ERASE -M $MEDIA$SAVLEVEL$SAVBANDNBR $FLAGR -f $SAVDEVICE /var/se3 >> $XFSLOG
	STATUS=`tail -n 1 $XFSLOG | cut -d : -f 3 | sed -e "s/ //g"`
	echo "sauvegarde de /var/se3: $STATUS" >>$SE3LOG
	if [ "$STATUS" = "SUCCESS" ]; then
		if [ "$SAVBANDNBR" != "0" ]; then
			echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		fi
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo -e "Succ�s de la sauvegarde.\n Pensez � faire une rotation de la bande en cas de besoin." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		echo "sauve.sh: La sauvegarde s'est termin�e avec succ�s" >> $SE3LOG
	fi
	if [ "$STATUS" = "INTERRUPT" ]; then
		echo "UPDATE params SET value=\"2\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		# Incr�mentation du compeur de bande
		let SAVBANDNBR+=1
		echo "UPDATE params SET value=\"$SAVBANDNBR\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo -e "Sauvegarde de /var/se3 inachev�e.\n Lorsque vous aurez ins�r� une nouvelle bande, �ditez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 0
	fi
	# Il y a des erreurs non r�cup�rables
	# Il faut recommencer le processus de savegarde entier /home et /var/se3 :-(
	# La sauvegarde est plac�e en �tat suspendu
	if [ "$STATUS" = "QUIT" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /var/se3: Le m�dia n'est plus utilisable, changez de bande puis �ditez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "INCOMPLETE" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /var/se3: La sauvegarde a �t� interrompue. Editez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "FAULT" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /var/se3: Erreur logicielle. Editez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "ERROR" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /var/se3: Erreur ressource. Editez les parametres de sauvegarde et mettez la variable savsuspend � 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
fi
