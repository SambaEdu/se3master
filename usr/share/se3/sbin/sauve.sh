#!/bin/bash

# $Id$ #

##### Lance la sauvegarde sur bande #####

# Détection de la distrib

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

# Récupération des paramètres mysql

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
	echo "Sauvegarde désactivée"
	exit 0
fi

MELSAVADMIN=`echo "SELECT value FROM params WHERE name='melsavadmin'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVLEVEL=`echo "SELECT value FROM params WHERE name='savlevel'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVBANDNBR=`echo "SELECT value FROM params WHERE name='savbandnbr'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVDEVICE=`echo "SELECT value FROM params WHERE name='savdevice'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVHOME=`echo "SELECT value FROM params WHERE name='savhome'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVSE3=`echo "SELECT value FROM params WHERE name='savse3'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
SAVSUSPEND=`echo "SELECT value FROM params WHERE name='savsuspend'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

# Vérification de l'intégrité des paramètres

echo "-----------------------------" >> $SE3LOG
date >> $SE3LOG
echo "-----------------------------" >> $SE3LOG

if [ -z "SAVDEVICE" ]; then
	echo "Le périphérique de sauvegarde n'est pas renseigné ou l'accès à la base des paramètres est impossible. La sauvegarde a échouée." >> $SE3LOG
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
			# La sauvegarde est achevée avec succes
			if [ "$SAVBANDNBR" != "0" ]; then
				echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
			fi
			echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
			echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
			echo -e "Succès de la sauvegarde.\n Pensez à faire une rotation de la bande en cas de besoin." | mail -s "Sauvegarde SE3" $MELSAVADMIN
			echo "sauve.sh: La sauvegarde s'est terminée avec succès" >> $SE3LOG
		fi
	fi
	if [ "$STATUS" = "INTERRUPT" ]; then
		echo "UPDATE params SET value=\"2\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		# Incrémentation du compeur de bande
		let SAVBANDNBR+=1
		echo "UPDATE params SET value=\"$SAVBANDNBR\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo -e "Sauvegarde de /home inachevée.\n Lorsque vous aurez inséré une nouvelle bande, éditez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 0
	fi
	# Il y a des erreurs non récupérables
	# Il faut recommencer le processus de savegarde entier /home et /var/se3 :-(
	# La sauvegarde est placée en état suspendu
	if [ "$STATUS" = "QUIT" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /home: Le média n'est plus utilisable, changez de bande puis éditez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "INCOMPLETE" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /home: La sauvegarde est incomplete. Editez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "FAULT" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /home: Erreur logicielle. Editez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "ERROR" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /home: Erreur ressource. Editez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
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
		echo -e "Succès de la sauvegarde.\n Pensez à faire une rotation de la bande en cas de besoin." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		echo "sauve.sh: La sauvegarde s'est terminée avec succès" >> $SE3LOG
	fi
	if [ "$STATUS" = "INTERRUPT" ]; then
		echo "UPDATE params SET value=\"2\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		# Incrémentation du compeur de bande
		let SAVBANDNBR+=1
		echo "UPDATE params SET value=\"$SAVBANDNBR\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo -e "Sauvegarde de /var/se3 inachevée.\n Lorsque vous aurez inséré une nouvelle bande, éditez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 0
	fi
	# Il y a des erreurs non récupérables
	# Il faut recommencer le processus de savegarde entier /home et /var/se3 :-(
	# La sauvegarde est placée en état suspendu
	if [ "$STATUS" = "QUIT" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /var/se3: Le média n'est plus utilisable, changez de bande puis éditez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "INCOMPLETE" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /var/se3: La sauvegarde a été interrompue. Editez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "FAULT" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /var/se3: Erreur logicielle. Editez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
	if [ "$STATUS" = "ERROR" ]; then
		echo "UPDATE params SET value=\"1\" WHERE name=\"savhome\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savse3\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"1\" WHERE name=\"savsuspend\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "UPDATE params SET value=\"0\" WHERE name=\"savbandnbr\""| mysql -h $dbhost $dbname -u $dbuser -p$dbpass
		echo "Erreur lors de la sauvegarde de /var/se3: Erreur ressource. Editez les parametres de sauvegarde et mettez la variable savsuspend à 0." | mail -s "Sauvegarde SE3" $MELSAVADMIN
		exit 1
	fi
fi
