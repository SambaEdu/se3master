#!/bin/bash

## $Id$  ##


# Script destine a gerer les comptes orphelins globalement en remplacement  de mv_Trash_Home.sh, delHome.pl 
# Auteur: Franck Molle
# Derniere modification: 10/2011


### recup params 
# /usr/share/se3/includes/config.inc.sh -l ----> marche pas avec le getops !!!
. /etc/se3/config_l.cache.sh

usage()
{
	echo "script permettant le nettoyage des homes et / ou /var/se3 suite bascule annuelle" 
	echo "usage: $0 -c -d -o -m -t -s -v -h"
	echo "       -c :  clean - supressions vieux fichiers sans proprio"
	echo "       -d :  del - suppression des homes orphelins"
	echo "       -m :  move - deplacement des homes orphelins dans /home/admin/_trash_user"
	echo "       -s :  shedule  - lancement en tache at le soir "
	echo "       -o :  only - utile pour les maj"
	echo "       -t :  move only - deplacement vieux fichiers de home uniquement vers /home/admin/Trash_users"
	echo "       -v :  varse3 : scanne de la partition /var/se3 pour suppresion fichiers obsoletes"
	echo "       -h :  Show this help"
	exit $1
}


ldap_status()
{

if [ ! -e /var/run/slapd/slapd.pid ];then
	echo "ERREUR: Le serveur ldap ne semble pas fonctionner"
	echo "Interuption du script"
	exit 1
fi
}


if [ $# -eq "0" ]  # Script appele sans argument?
then
  echo "option incorrecte"
  usage 1 
fi






unset CLEAN DELETE ONLY SHEDUL MOVE TRASH VARSE3

VERSBOSE=0
while getopts ":cdostmvh" cmd
do
	case $cmd in	
	c) CLEAN=1 
	opt="c" ;;
	d) DELETE=1 ;;
	o) ONLY=1 
	opt="o" ;;
	s) SHEDUL=1 ;;
	t) TRASH=1 ;;
	v) VARSE3=1
	opt="v";;
	m) MOVE=1 
	opt="m" ;;
	h) usage 0 ;;
	\?) echo "bad option!"
	usage 1 ;;
	*) echo "bad option!"
	usage 1 ;;
	esac
done

### recup params 
# . /usr/share/se3/includes/config.inc.sh -mlv


if [ "$TRASH" = "1" ]; then 
  rm -rf /home/admin/Trash_users
fi



if [ "$SHEDUL" = "1" ]; then 
  at_script="/tmp/clean_homes_at.sh"
  cat > $at_script <<END
#!/bin/bash
$0 -$opt
END

  chmod 700 $at_script
  if [ "$ONLY" = "1" -o "$CLEAN" = "1" ]; then 
      at 20:00 -f $at_script
  else
      at now -f $at_script  
  fi
  exit 0
fi


if [ "$VARSE3" = "1" -o "$CLEAN" = "1" ]; then 
    echo "Recherche et suppression vieux fichiers sur le partage Classes"
    ldap_status
    find /var/se3/Classes/* -nouser -type f -print -exec rm -f "{}" \; 2>/dev/null
    find /var/se3/Classes/* -nouser -type d -print -exec rmdir "{}" \; 2>/dev/null

    echo "Recherche et suppression vieux fichiers sur partage Docs"
    ldap_status
    find /var/se3/Docs/* -nouser -type f -print -exec rm -f "{}" \; 2>/dev/null
    find /var/se3/Docs/* -nouser -type d -print -exec rmdir "{}" \; 2>/dev/null

    echo "Recherche et suppression vieux fichiers sur partage Progs"
    ldap_status
    find /var/se3/Progs/* -nouser -type f -print -exec rm -f "{}" \; 2>/dev/null
    find /var/se3/Progs/* -nouser -type d -print -exec rmdir "{}" \; 2>/dev/null

    echo "Recherche et suppression vieux fichiers sur partage prof"
    ldap_status
    find /var/se3/prof/* -nouser -type f -print -exec rm -f "{}" \; 2>/dev/null
    find /var/se3/prof/* -nouser -type d -print -exec rmdir "{}" \; 2>/dev/null
fi

if [ "$ONLY" = "1" -o "$CLEAN" = "1" ]; then 

    if [ -e /var/run/backuppc/BackupPC.pid ]; then
	invoke-rc.d backuppc stop
	bpc_etat="1"
    fi
    echo "Recherche et suppression vieux profils XP / Seven"
	ldap_status
    find /home/profiles/ -maxdepth 1  -type d -nouser -print -exec rm -rf "{}" \;
    #    find /home/admin/profiles/ -maxdepth 1  -type d -nouser -print -exec rm -rf "{}" \;
    #     find /home/ -maxdepth 4 -nouser -print -exec rm -rf "{}" \; 2>/dev/null


    if [ "$CLEAN" = "1" ]; then 
	echo "Recherche et suppression vieux fichiers /home/admin/Trash_users"
	find /home/admin/Trash_users -name _Trash_[0-9_]* -print -exec rm -rf "{}" \; 2>/dev/null
	
    fi

    LADATE=$(date +%d-%m-%Y)
    dest=/home/admin/Trash_users/_Trash_$LADATE

    cpt=0
    cd /home
	ldap_status
    ls /home | while read A
    do
	if [ -d "/home/$A"  -a ! -L /home/$A ]; then
		if [ ! -z "$(echo "$A" | grep -e "_Trash_[0-9_]*")" ]; then
		    # permet de corriger une erreur de quota en cas d'uid re-attribue
		    echo "Deplacement du dossier Trash $A dans /home/admin/ "
		    mkdir -p /home/admin/Trash_users/
		    chown admin:admins /home/admin/Trash_users/
		    mv /home/$A /home/admin/Trash_users/
		    chown -R admin:admins /home/admin/Trash_users/$A 
		else 
		    if [ "$A" != "templates" -a "$A" != "netlogon" -a "$A" != "admin" -a "$A" != "samba" -a "$A" != "sauvegarde" -a "$A" != "profiles" ]; then
				if [ -z "$(getfacl $A 2>/dev/null|grep owner|grep $A)"  ]; then
			  
			    	echo "$A n'est pas proprio de son Home... mise en $dest."
					if [ "$cpt" = "0" ]; then
						mkdir -p /home/admin/Trash_users
						chown admin:admins /home/admin/Trash_users
						mkdir -p ${dest}
					fi
					mv /home/$A $dest/
					rm -rf /home/profiles/$A
					chown -R admin:admins $dest/$A
					cpt=1
			    
				fi  
		    
			fi
		fi
	fi
	
	
	
    done

    

    if [ "$bpc_etat" = "1" ]; then
	    invoke-rc.d backuppc start
    fi



fi
if  [ "$DELETE" = "1" -o "$MOVE" = "1" ]; then
    dest=/home/admin/Trash_users/_Trash_$(date +%Y%m%d_%H%M%S)
    fich=/var/www/se3/Admin/mv_Trash_$(date +%Y%m%d%H%M%S)
    cpt=0
	   
    echo "Parcours de la Corbeille...<br />"
    ldapsearch -xLLL -b ou=Trash,$ldap_base_dn uid | grep "^uid: " | sed -e "s/^uid: //" | while read uid
    do
	#echo "Controle de $uid"
	  if [ -d "/home/$uid" ]; then
		if [ "$MOVE" = "1" ]; then 
		
		    if [ "$cpt" = "0" ]; then
			  mkdir -p /home/admin/Trash_users
			  chown admin:admins /home/admin/Trash_users
			  mkdir -p ${dest}
			  if [ "$?" != "0" ]; then
			      echo "ERREUR: La creation du dossier ${dest} a echoue."
			      exit 1
			  fi
			  echo "Deplacement vers ${dest}: <br>"
		    else
			  echo ", "|tee -a $fich
		    fi
		    echo "$uid"|tee -a $fich
		    mv /home/$uid ${dest}/
		    chown -R admin ${dest}/$uid 
			rm -f /var/se3/Docs/trombine/$uid.* 
# 		   echo "mv /home/$uid ${dest}/ <br>"

		    cpt=$(($cpt+1))
		else
			echo  "Suppression de /home/$uid <br>"
			rm -fr /home/$uid 
			rm -f /var/se3/Docs/trombine/$uid.* 
#  		     echo "rm -fr /home/$uid <br>" 
	   fi
	    
	  else
	    echo "/home/$uid n'existe pas <br>"
	  fi

	# A VOIR pour LCS:
	# Faut-il un dump de sauvegarde?...
	#db_name=$(echo "$uid" | sed -e "s/-//g" | sed -e "s/_//g" | sed -e "s/\.//g")
	#echo "DROP DATABASE ${db_name}" | mysql -h $dbhost -u $dbuser -p$dbpass > /dev/null 2>&1
	#echo "delete from personne where login = '$uid'" | mysql -h $dbhost lcs_db -u $dbuser -p$dbpass
    done

fi


# if [ "$BACKUP" = "1" ]; then 
# getmysql "5" $VERSBOSE /etc/se3/config_b.cache.sh
# fi











