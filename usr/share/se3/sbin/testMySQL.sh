#!/bin/bash
#
## $Id$ ##
#
##### Test la base MySQL #####
#

LISTE_BASE="se3db ocsweb"
FICHIER_RESULT="/root/base_mysql.test"
	
	
# Récupération des paramètres mysql
#
if [ "$1" = "-h" -o "$1" = "-help" -o "$1" = "-c" -o "$1" = "-v" -o "$1" = "" ]
then
	if [ "$1" = "--help" -o "$1" = "-h" ]
	then
		echo "Test la base MySQL"
		echo "Usage : ./testMySQL.sh -[option] (une seule option de possible à la fois)"
		echo "-v verbose"
		echo "-c pemet de générer dans $FICHIER_RESULT une image de la base de donnée."
		echo "N'utiliser cette option qu'en connaissance de cause"
		exit
	fi	
	
	if [ "$1" = "-c" ]
	then
	   	if [ -e $FICHIER_RESULT ]
	   	then
	   		rm -f $FICHIER_RESULT
			touch $FICHIER_RESULT
	   	else 	
			touch $FICHIER_RESULT
	   	fi	
		for i in $LISTE_BASE 
		do	
			cd /var/lib/mysql/$i
	        	A=`find * -iname "*.MYD" | cut -d. -f1`
		        for j in $A
			do
				ROWS=""
				ROWS=`mysql -D $i -e "show fields from $j" | wc -l`
				ROWS=$(($ROWS - 1))
				echo "$i $j $ROWS" >> $FICHIER_RESULT
			done
		done	
		exit
	fi
			
	# Test le compte de connexion pour les pages php
	if [ -e /var/www/se3/includes/config.inc.php ]
	then
	        dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	        dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
	        dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
	        dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
	
		# test la connexion avec ce compte
		CONNEX_SE3DB=`mysqlshow $dbname -u $dbuser -p$dbpass | grep params > /dev/null && echo 1`
		if [ "$CONNEX_SE3DB" = "1" ]
		then
			if [ "$1" = "-v" ]
			then
				echo "Connexion avec le compte de l'interface --> Ok"
			fi
		else
			if [ "$1" = "1" ]
			then
				echo "Connexion avec le compte de l'interface --> Failed"
			fi
			echo "Failed"
			exit 1
		fi	
			
	else
		if [ "$1" = "-v" ]
		then
	        	echo "Fichier de conf  pour php inaccessible"
		fi
		echo "Failed"
	        exit 1
	fi
	
	# Vérifie fichier my.cnf
	if [ -e /root/.my.cnf ]
	then
		dbuser_my=`cat /root/.my.cnf | grep user= | cut -d= -f2`
		dbpass_my=`cat /root/.my.cnf | grep password= | cut -d= -f2`
		# essaye de se connecter
		CONNEX_mysql=`mysqlshow -u $dbuser_my -p$dbpass_my | grep mysql  > /dev/null && echo 1`
		if [ "$CONNEX_mysql" = "1" ]
		then
		  if [ "$1" = "-v" ]
		  then
			echo "Connexion avec le compte root --> Ok"
		  fi	
			# Si ok on peut tester la présence de toutes les bases
			for i in  se3db # $LISTE_BASE  => ocs non obligatoire!
			do
				CONNEX_BD=`mysqlshow -u $dbuser_my -p$dbpass_my | grep $i > /dev/null && echo 1`
				
				if [ "$CONNEX_BD" = "1" ]
				then
				   if [ "$1" = "-v" ]
				   then
					echo "Base $i  --> Ok"
				   fi	
				else 
				   if [ "$1" = "-v" ]
				   then
					echo "Base $i --> Failed"
				   fi
				   echo "Failed"
			   	   exit 1
				fi	
			done	
			
			# test complet à partir du fichier si celui-ci existe 	
			if [ -e $FICHIER_RESULT ]
			then
			   	if [ "$1" = "-v" ]
			   	then
			   		echo "Fichier de comparaison existe"
			   	fi	
				cat $FICHIER_RESULT | while read L
				do
					CONNEX=""
					BASE=`echo $L | cut -d" " -f1`
					TABLE=`echo $L | cut -d" " -f2`
					CHAMP=`echo $L | cut -d" " -f3`
					CONNEX=`mysql -u $dbuser_my -p$dbpass_my -D $BASE -e "select count(*) from $TABLE" 2>/dev/null | grep -v ERROR >/dev/null && echo 1`
					if [ "$CONNEX" = "1" ]
					then
						# On peut tester le nombre de champs
						ROWS=""
						ROWS=`mysql -u $dbuser_my -p$dbpass_my -D $BASE -e "show fields from $TABLE" | wc -l`
						ROWS=$(($ROWS - 1))
						if [ "$ROWS" != "$CHAMP" ]
						then
						   if [ "$1" = "-v" ]
						   then
							echo "Nombre de champs attendu $CHAMP dans la table $TABLE ($BASE), présent $ROWS --> Failed"
						   fi
						   exit 1
						fi	
					else
					        if [ "$1" = "-v" ]
					        then
							echo "Connexion à la table $TABLE --> Failed"
						fi
						exit 1
					fi	
				done
				if [ "$?" = "1" ]
				then
					echo "Failed"
					exit 1
				fi	
			else 
				if [ "$1" = "-v" ]
				then
					echo "Vous ne possédez pas le fichier de référence"
					echo "La comparaison avec ce que votre base devrait être ne peut être réalisée"
				fi	
			fi	
		else
			if [ "$1" = "-v" ]
			then
				echo "Connexion au serveur MySQL impossible avec le mot de passe -> Failed"
			fi
			echo "Failed"
			exit 1
		fi

		
	else
		if [ "$1" = "-v" ]
		then
			echo "Erreur my.cnf inexistant"
		fi
		echo "Failed"
		exit 1
	fi

	dpkg -l | grep -q se3-ocs && ocs="yes"
	if [ "$ocs" == "yes" ]
	then


		# Test le compte de connexion pour les pages php
		if [ "$1" = "-v" ]
		then
			echo "Test le mot de passe pour l'inventaire"
		fi	
		if [ -e /var/www/se3/includes/dbconfig.inc.php ]
		then


			dbhostinvent="localhost"
		        dbnameinvent="ocsweb"
		        dbuserinvent="ocs"
		        dbpassinvent=`cat /var/www/se3/includes/dbconfig.inc.php | grep "PSWD_BASE" | cut -d\" -f4`
			# test la connexion avec ce compte
			CONNEX_INVENT=`mysqlshow $dbnameinvent -u $dbuserinvent -p$dbpassinvent | grep hardware > /dev/null && echo 1`
			if [ "$CONNEX_INVENT" = "1" ]
			then
				if [ "$1" = "-v" ]
				then
					echo "Connexion avec le compte de l'interface inventaire --> Ok"
				fi
			else
				if [ "$1" = "1" ]
				then
					echo "Connexion avec le compte de l'interface inventaire --> Failed"
				fi
				echo "Failed"
				exit 1
			fi	
		fi
	fi


else
	echo "Usage : ./testMySQL.sh -h pour l'aide"
	exit
fi	

