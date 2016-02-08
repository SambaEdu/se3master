#!/bin/bash


#
## $Id$ ##
#

##### Script permettant le reglage des quotas des groupes utilisateurs #####
#


#Couleurs
COLTITRE="\033[1;35m"	# Rose
COLPARTIE="\033[1;34m"	# Bleu
COLTXT="\033[0;37m"	# Gris
COLCHOIX="\033[1;33m"	# Jaune
COLDEFAUT="\033[0;33m"	# Brun-jaune
COLSAISIE="\033[1;32m"	# Vert
COLCMD="\033[1;37m"	# Blanc
COLERREUR="\033[1;31m"	# Rouge
COLINFO="\033[0;36m"	# Cyan


ERREUR()
{
	echo -e "$COLERREUR"
	echo "ERREUR!"
	echo -e "$1"
	echo -e "$COLTXT"
	exit 1
}

SET_QUOTA()
# fonction pour fixer les quotas
# arguments :
# user soft hard path
{
fstype=$(grep  $4 /etc/mtab)
if $(echo $fstype | grep -q xfs); then
	/usr/sbin/setquota -F xfs $1 $2 $3 0 0 $4
elif $(echo $fstype | grep -q zfs); then
	zvol=$(echo $fstype | awk '{ print $1 }')
	newquota=$(( $2 * 1049 ))
	/sbin/zfs set userquota@$1=$newquota $zvol
else
	/usr/sbin/setquota $1 $2 $3 0 0 $4
fi
}

WWWPATH="/var/www"
## recuperation des variables necessaires pour interoger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 | cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut	-d = -f 2 | cut -d \" -f 2`
 	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
 	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
	ERREUR "Fichier de configuration inaccessible, le script ne peut se poursuivre."
	
fi

#patch 1/6 pour application imm�diate des quotas
CREER_FICHIER()
{
    if [ "$2" == "/home" ]; then
      #~ /usr/share/se3/sbin/mkhome.pl $1
      
### protège les serveurs vmware (essentiellement) contre la création des homes de tous les users par création d'un fichier vide sur /home
      if [ ! -e /home/$1 ] ; then
        if [ ! -e /home/quotas_tmp ]; then
          mkdir /home/quotas_tmp
          #~ cree="yes"
          chmod 700 /home/quotas_tmp
        fi
        
        if [ ! -e /home/quotas_tmp/$1 ]; then
          touch /home/quotas_tmp/$1
          chown $1 /home/quotas_tmp/$1
        fi
      fi
    else
      if [ ! -e /var/se3/quotas_tmp ]; then
        mkdir /var/se3/quotas_tmp
        #~ cree="yes"
        chmod 700 /var/se3/quotas_tmp
      fi
      
      if [ ! -e /var/se3/quotas_tmp/$1 ]; then
        touch /var/se3/quotas_tmp/$1
        chown $1 /var/se3/quotas_tmp/$1
      fi
    fi
}

if [ $# -ne 4 -o "$1" = "--help" -o "$1" = "-h" ]; then
	echo -e "$COLERREUR\c"
	echo -e "$0 a besoin d'arguments pour fonctionner"
	echo -e "$COLINFO\c"
	echo "Passer en arguments dans l'ordre :"
	echo "- le nom du groupe ou de l'utilisateur dont vous voulez fixer le quota"
	echo "- le quota soft à fixer"
	echo "- le quota hard à fixer"
	echo "- la partition sur laquelle on applique le quota"
	echo -e "$COLTXT"
	echo "ex1 : ./quota.sh Profs 200Mo 200Mo /home"
	echo "fixera un quota de 200Mo soft et hard sur home pour chaque prof"
	echo ""
	echo "ex2: ./quota.sh hugov 1Go 1Go /home"
	echo "fixera un quota de 1go soft et hard sur home pour l'utilisateur hugov"
	exit 1
fi

if [ ! -e /home -a "$4" == "/home" ]; then
	ERREUR "Pas de répertoire /home"
fi

	### recuperation des parametres actuels de l'annuaire dans la base ####
	BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	

SOFT_QUOTA=$(echo "$2" | sed -e "s/[gG][oO]/000000/" | sed -e "s/[Mm][Oo]/000/" | sed -e "s/[Aa]ucun/0/")
CONV_SOFT_QUOTA=$(echo "$SOFT_QUOTA" | sed -e "s/000000\$/Go/" | sed -e "s/000\$/Mo/")

HARD_QUOTA=$(echo "$3" | sed -e "s/[gG][oO]/000000/" | sed -e "s/[Mm][Oo]/000/" | sed -e "s/[Aa]ucun/0/")
CONV_HARD_QUOTA=$(echo "$HARD_QUOTA" | sed -e "s/000000\$/Go/" | sed -e "s/000\$/Mo/")

if [ $SOFT_QUOTA -eq 0  ]; then
CONV_SOFT_QUOTA="Aucun"
fi

if [ "$HARD_QUOTA" -eq 0 ]; then
CONV_HARD_QUOTA="Aucun"
fi


if [ -e /usr/sbin/setquota ]; then
        #~ #patch 2/6 pour application immédiate des quotas
        #~ if [ "$4" == "/var/se3" -a ! -e /var/se3/quotas_tmp ]; then
          #~ mkdir /var/se3/quotas_tmp
          #~ cree="yes"
          #~ chmod 700 /var/se3/quotas_tmp
        #~ fi
        
	TST_GRP=$(ldapsearch -xLLL cn=$1 -b $BASEDN member | grep member)
	
	if [ -z "$TST_GRP" ]; then
	TST_UID=$(ldapsearch -xLLL uid="$1" uid)
		if [ -z "$TST_UID" ]; then
			ERREUR "Impossible de trouver le groupe ou l'utilisateur passé en paramètre dans l'annuaire Ldap"
		else
			if [ "$1" != "admin" -a "$1" != "root" -a "$1" != "www-se3" ]; then
				echo "je fixe le quota pour l'utilisateur  $1 sur la partition $4"
                                #patch 3/6 pour application immédiate des quotas
                                CREER_FICHIER $1 $4
				SET_QUOTA $1 $SOFT_QUOTA $HARD_QUOTA $4
				echo "quota soft : $CONV_SOFT_QUOTA"
				echo "quota hard : $CONV_HARD_QUOTA"
				echo
			else
				echo "Par securit� on ne peut pas fixer de quota sur $1"
			fi
		fi
	fi
	TST_GRP_POSIX=$(ldapsearch -xLLL "cn=$1" memberUid | grep memberUid)
	if [ -z "$TST_GRP_POSIX" ]; then
		ldapsearch -x -LLL cn=$1 -b $BASEDN | grep uid | cut -d " " -f2 |  cut -d "=" -f2 | cut -d "," -f1 | while read A
		do
			if [ "$A" != "admin"  -a "$A" != "root" -a "$A" != "www-se3" ]; then
				echo "je fixe le quota pour $A sur la partition $4"
                                #patch 4/6 pour application immédiate des quotas
                                CREER_FICHIER $A $4
				SET_QUOTA $A $SOFT_QUOTA $HARD_QUOTA $4
				echo "quota soft : $CONV_SOFT_QUOTA"
				echo "quota hard : $CONV_HARD_QUOTA"
				echo
			else
				echo "Par securité on ne peut pas fixer de quota sur $A"
			fi
		done
	
	else
		
		ldapsearch -x -LLL "cn=$1" memberUid | grep memberUid | cut -d " " -f2 | while read A
		do 
			if [ "$A" != "admin"  -a "$A" != "root" -a "$A" != "www-se3" ]; then
				echo "je fixe le quota pour $A sur la partition $4"
                                #patch 5/6 pour application immédiate des quotas
                                CREER_FICHIER $A $4
				SET_QUOTA $A $SOFT_QUOTA $HARD_QUOTA $4
				echo "quota soft : $CONV_SOFT_QUOTA"
				echo "quota hard : $CONV_HARD_QUOTA"
				echo
			else
				echo "Par securité on ne peut pas fixer de quota sur $A"
			fi
		done
	fi
        
        #patch 6/6 pour application immédiate des quotas
        #if [ "$cree" == "yes" ]; then
          rm -R /home/quotas_tmp > /dev/null 2>&1
          rm -R /var/se3/quotas_tmp > /dev/null 2>&1 # si plusieurs scripts quota.sh sont lancés en // le répertoire peut avoir déjà été supprimé
        #fi
else
	ERREUR "Le paquet quota n'est pas installé.\nEffectuez:\n\tapt-get update\n\tapt-get install quota"
fi
exit 0
