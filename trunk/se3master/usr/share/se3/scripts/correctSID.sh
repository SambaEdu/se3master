#!/bin/bash

#
## $Id$ ##


#
##### Change dans l'annuaire LDAP le SID en fonction du SID existant dans la base MySQL #####
#

REPSAVE=/var/se3/save/ldap

function usage {
        echo "Synchronise le SID samba stocke dans l'annuaire LDAP, mysql (se3db) et dans le secrets.tdb."
	echo "Attention: avant de lancer ce script, verifier la coherence du SID avec testSID.sh"
	echo
        echo "Usage : Aucune option pour un script interactif"
        echo "        -t teste les sid UNIQUEMENT : renvoie 0 si tout va bien, 1 en cas d'erreur (le script ne proposera pas de corriger)"
        echo "        -s importe le sid du secrets.tdb de samba"
        echo "        -m importe le sid de mysql (table params de se3db) (option par defaut)"
	echo "        -l si l'annuaire ne comporte qu'un seul SID, corrige par rapport à l'annuaire"
        echo "        -q mode silencieux (corrige les erreurs)"
        echo "        --noldapsave ne sauvegarde pas l'annuaire LDAP avant de le corriger (DANGEREUX)"
        echo "        -c corrige le mot de passe AdminPw LDAP dans le secrets.tdb (cas de probleme de connexion generalise des clients)"
		echo "        --videcache vide les fichiers cache tdb de samba : permet de resoudre des problemes generalises d'impression, d'integration..."
		echo "        --simulation genere le fichier de correction LDAP mais ne corrige pas"
        echo ""
        echo "Remarques: -t l'emporte sur tout le reste : seul le testSID est effectue."
        echo "           -s , -l et -m sont incompatibles. -m l'emporte !"
	exit $1;
}

while getopts "cqmshtl-:" cmd
do
	if [ "$cmd" == "-" ] ; then
              case $OPTARG in
                        noldapsave ) NOLDAPSAVE=1 ;;
						videcache ) VIDECACHE=1;;
						simulation ) SIMUL=1 ;;
                        * ) echo "option longue inconnue..."
                            usage 1 ;;
              esac
        else
              case $cmd in
                        q) QUIET=1 ;;
			s) if [ "$MYSQLSID" == "" ]; then
                        MYSQLSID=0 
                        fi ;;
			m) MYSQLSID=1 ;;
                        c) CORRECTADMINPWSAMBA=1 ;;
                        t) TESTSIDOPT=1 ;;
                        # ajout de la possibilite de restaurer par rapport a l'annuaire LDAP pour permettre une restauration automatique depuis backuppc
			l) MYSQLSID=2 ;;
			h) usage 0 ;;
			?) echo "BAD OPTION"
			   usage 1 ;;
              esac
        fi
done

ERREUR()
{
        echo "ERREUR!"
        echo -e "$1"
        exit 1
}

POURSUIVRE()
{
        REPONSE=""
        while [ "$REPONSE" != "o" -a "$REPONSE" != "O" -a "$REPONSE" != "n" ]
        do
                echo -n "Doit-on poursuivre? (o/N)"
                read REPONSE
                if [ -z "$REPONSE" ]; then
                        REPONSE="n"
                fi
        done

        if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
                ERREUR "Abandon!"
        fi
}

################### initialisation des variables ###################################
# Recuperation des parametres mysql
if [ -e /var/www/se3/includes/config.inc.php ]; then
        dbhost=`cat /var/www/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
        dbname=`cat /var/www/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
        dbuser=`cat /var/www/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 |cut -d \" -f 2`
        dbpass=`cat /var/www/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 |cut -d \" -f 2`
else
        echo "Fichier de conf inaccessible" >> $SE3LOG
		echo "sauve.sh: Status FAILED" >> $SE3LOG
        exit 1
fi

# Recuperation des params LDAP
BASEDN=`echo "SELECT value FROM params WHERE name='ldap_base_dn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$BASEDN" ]; then
        echo "Impossible d'acceder au parametre BASEDN"
        exit 1
fi
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINRDN" ]; then
        echo "Impossible d'acceder au parametre ADMINRDN"
        exit 1
fi
ADMINPW=`echo "SELECT value FROM params WHERE name='adminPw'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$ADMINPW" ]; then
        echo "Impossible d'acceder au parametre ADMINPW"
        exit 1
fi
COMPUTERDN=`echo "SELECT value FROM params WHERE name='computersRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$COMPUTERDN" ]; then
        echo "Impossible d'acceder au parametre COMPUTERDN"
        exit 1
fi
SMBVERSION=`echo "SELECT value FROM params WHERE name='smbversion'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
if [ -z "$SMBVERSION" ]; then
        echo "Impossible d'acceder au parametre smbversion"
        exit 1
fi
if [ ! "$SMBVERSION" = "samba3" ]; then
	echo "Version de samba incorrecte."
	exit 1
fi

# slapd doit etre demarre pour les ldapmodify et ldapdelete
/etc/init.d/slapd start > /dev/null

MYSQLDOMAINSID=`echo "SELECT value FROM params WHERE name='domainsid'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
OLDDOMAINSID=`net getlocalsid  |cut -d : -f 2 |sed -e "s/ //g"`
dateheure=`date +%Y%m%d_%T`

mkdir -p $REPSAVE
FICHIER_CORRECTIONS_LDAP=$REPSAVE/correctSIDmod_$dateheure.ldif

LISTESID1=`ldapsearch -xLLL | grep "sambaSID"  | grep -v "^sambaSID: S-1-5-32-546$" | cut -d"-" -f1-7 | sort | cut -d" " -f2 | uniq`
LISTESID2=`ldapsearch -xLLL | grep "sambaPrimaryGroupSID" | grep -v "^sambaPrimaryGroupSID: S-1-5-32-546$" | cut -d"-" -f1-7 | sort | cut -d" " -f2 | uniq`
LISTESID=`echo -e "$LISTESID1\n$LISTESID2" | sort | uniq`

###################### partie correction des sid (affichee si besoin) ############################
if [ "`echo "$LISTESID" | grep -v "$MYSQLDOMAINSID"`" != "" -o "`echo "$MYSQLDOMAINSID" | grep "$OLDDOMAINSID"`" == "" ]; then
    if [ "$TESTSIDOPT" == "1" ]; then
      # on remplace le script testsid.sh par correctSID.sh -t
      ERREUR "SID incoherents entre ldap, mysql et samba (secrets.tdb)."
    fi
    
    ############# affichage des sid presents ##############
    echo "Le SID actuellement dans la table params est :"
    echo "$MYSQLDOMAINSID"
    echo 
    echo "Le SID actuellement utilise par samba (secrets.tdb) est :"
    echo "$OLDDOMAINSID"
    echo 
    echo "Le(s) SID present(s) dans l'annuaire sont :"
    echo "$LISTESID"
    echo
    
    ############## correction des sid ##################
    if [ "$QUIET" != "1" -a "$MYSQLSID" == "" ]; then
      if [ "$SIMUL" == "1" ]; then
      	echo "*************** SIMULATION DE CORRECTION DU SID ******************"
      else
      	echo "*************** CORRECTION DU SID ******************"
      fi
      echo "1) en fonction de la table params de mysql"
      echo "2) en fonction du sid SAMBA contenu dans le secrets.tdb"
      [ "`echo "$LISTESID" | wc -l`" == "1" ] && echo "3) en fonction de l'unique sid present dans l'annuaire"
      echo "Autre) Quitter"
      echo "****************************************************"
      if [ "$SIMUL" == "1" ]; then
	echo "VOUS NE RISQUEZ RIEN : MODE SIMULATION."
      fi
      echo -n "Choix du mode de migration (quitter par defaut): "
      read choix
      case $choix in
          1) MYSQLSID=1 ;;
          2) MYSQLSID=0 ;;
          3) [ "`echo "$LISTESID" | wc -l`" == "1" ] && MYSQLSID=2 ;;
          *) exit ;;
      esac
    fi
    
    # option par defaut : on regle par rapport au SID mysql
    if [ "$MYSQLSID" == "" ]; then
      MYSQLSID=1
    fi
    
    # le DOMAINSID qui sert a toutes les corrections ulterieures est rempli
    if [ "$MYSQLSID" == "1" ]; then
      echo "Correction en fonction de mysql..."
      DOMAINSID="$MYSQLDOMAINSID"
    else
	if [ "$MYSQLSID" == "0" ]; then
	      echo "Correction en fonction du secrets.tdb..."
	      DOMAINSID=`net getlocalsid  |cut -d : -f 2 |sed -e "s/ //g"`
	else
	# ajout de la possibilite de restaurer par rapport a l'annuaire LDAP pour restauration automatisee depuis backuppc
	      echo "Correction en fonction de l'annuaire LDAP..."
	      DOMAINSID="$LISTESID"
	fi
    fi
    
    if [ "$NOLDAPSAVE" == "1" -o "$SIMUL" == "1" ]; then
      # pour migration etch notamment et autres scripts sauvegardant l annuaire par ailleurs
      echo "Pas de sauvegarde de l'annuaire a cause des options --noldapsave ou --simulation"
      echo
    else
      echo "Sauvegarde de l'annuaire actuel dans $REPSAVE/export_$dateheure.ldif"
      echo
      slapcat > $REPSAVE/export_$dateheure.ldif
    fi
    
    if [ "$MYSQLSID" != "1" ]; then
      if [ "$MYSQLDOMAINSID" != "$DOMAINSID" ]; then
        echo "Les sid mysql et celui choisi $DOMAINSID ne correspondent pas !"
        if [ "$SIMUL" == "1" ] ; then
		echo "Le SID mysql pourrait etre corrige dans la table params en fonction du sid choisi : $DOMAINSID ."
	else
		echo "Le SID mysql va etre corrige dans la table params en fonction du sid choisi : $DOMAINSID ."
        	if [ "1" != "$QUIET" ]; then
        	  POURSUIVRE
        	fi 
        	mysql -D $dbname -e "UPDATE params SET value=\"$DOMAINSID\" WHERE name='domainsid'"
	fi
      fi
    fi
    
    if [ "$MYSQLSID" != "0" ]; then
      if [ "`echo "$DOMAINSID" | grep -v "$OLDDOMAINSID"`" != "" ]; then
        echo "Le sid du secrets.tdb et celui choisi $DOMAINSID ne correspondent pas !!"
        echo "Correction du SID samba a effectuer par rapport au SID choisi : $DOMAINSID ."
        if [ "$SIMUL" == "1" ]; then
		echo "Aucune modification effectuee"
		echo
	else
		if [ "1" != "$QUIET" ]; then
        	  POURSUIVRE
        	fi
        	net setlocalsid $DOMAINSID
	fi
      fi
    fi
    
    if [ "`echo "$LISTESID" | grep -v "$DOMAINSID"`" != "" ]; then
      echo "Certains sid de l'annuaire ldap ne correspondent pas au choix effectue : $DOMAINSID ."
      echo "L'ensemble de l'annuaire doit etre corrige en fonction du SID choisi !!"
      if [ "1" != "$QUIET" -a "$SIMUL" != "1" ]; then
        POURSUIVRE
      fi
      echo "Patience..."
      
      # On prepare les modifs
      ########### correction du sambaDomain ######################
      ligne=`ldapsearch -xLLL objectClass=sambaDomain dn | grep dn |cut -d, -f -1 |cut -c 5-`
      ERR=1
      uid=`echo $ligne|cut -d, -f1`
      ldapsearch -xLLL "$uid" sambaSID | egrep "$DOMAINSID|^sambaSID: S-1-5-32-546$" >/dev/null&& ERR=0
      if [ $ERR = 1 ]; then
              echo "dn: $ligne,$BASEDN">$FICHIER_CORRECTIONS_LDAP
              echo "changetype: modify">>$FICHIER_CORRECTIONS_LDAP
              echo "replace: sambaSID">>$FICHIER_CORRECTIONS_LDAP
              echo "sambaSID: $DOMAINSID">>$FICHIER_CORRECTIONS_LDAP
              echo "">>$FICHIER_CORRECTIONS_LDAP
      fi
      
      ########### correction du sambaSamAccount ######################
      ldapsearch -xLLL objectClass=sambaSamAccount dn | grep dn | cut -d, -f -2 | cut -c 5- | while true
      do
        read ligne
        if [ -z "$ligne" ]; then
                break;
        fi
        ERR=1
        uid=`echo $ligne|cut -d, -f1`
        ldapsearch -xLLL "$uid" sambaSID | egrep "$DOMAINSID|^sambaSID: S-1-5-32-546$" >/dev/null&& ERR=0
        tst_root=`echo $ligne|grep cn=root`
        [ ! -z $tst_root ] && ligne=$(echo $ligne | cut -d, -f1)
        
        if [ $ERR = 1 ]; then
                rid=`ldapsearch -xLLL "$uid" sambaSID |grep sambaSID |cut -d- -f8`
                echo "dn: $ligne,$BASEDN">>$FICHIER_CORRECTIONS_LDAP
                echo "changetype: modify">>$FICHIER_CORRECTIONS_LDAP
                echo "replace: sambaSID">>$FICHIER_CORRECTIONS_LDAP
                echo "sambaSID: $DOMAINSID-$rid">>$FICHIER_CORRECTIONS_LDAP
                echo "">>$FICHIER_CORRECTIONS_LDAP
        fi
        
        ERR=1
        ldapsearch -xLLL "$uid" sambaPrimaryGroupSID | egrep "$DOMAINSID|S-1-5-32-546$" >/dev/null&& ERR=0
        
        
        if [ $ERR = 1 ]; then
                pid=`ldapsearch -xLLL "$uid" sambaPrimaryGroupSID |grep sambaPrimaryGroupSID |cut -d- -f8`
                echo "dn: $ligne,$BASEDN">>$FICHIER_CORRECTIONS_LDAP
                echo "changetype: modify">>$FICHIER_CORRECTIONS_LDAP
                echo "replace: sambaPrimaryGroupSID">>$FICHIER_CORRECTIONS_LDAP
                echo "sambaPrimaryGroupSID: $DOMAINSID-$pid">>$FICHIER_CORRECTIONS_LDAP
                echo "">>$FICHIER_CORRECTIONS_LDAP
        fi
      done
      
      # correction des groupes mappes
      ldapsearch -xLLL objectClass=sambaGroupMapping dn | grep dn | cut -d, -f -2 | cut -c 5- | while true
      do
        read ligne
        if [ -z "$ligne" ]; then
                break;
        fi
        ERR=1
        uid=`echo $ligne|cut -d, -f1`
        ldapsearch -xLLL "$uid" sambaSID | egrep "$DOMAINSID|^sambaSID: S-1-5-32-546$" >/dev/null&& ERR=0
	 [ "$uid" == "cn=nogroup" ] && ERR=0
        if [ $ERR = 1 ]; then
                tst_root=`echo $ligne|grep cn=root`
                [ ! -z $tst_root ] && ligne=$(echo $ligne | cut -d, -f1)
                rid=`ldapsearch -xLLL "$uid" sambaSID |grep sambaSID |cut -d- -f8`
                echo "dn: $ligne,$BASEDN">>$FICHIER_CORRECTIONS_LDAP
                echo "changetype: modify">>$FICHIER_CORRECTIONS_LDAP
                echo "replace: sambaSID">>$FICHIER_CORRECTIONS_LDAP
                echo "sambaSID: $DOMAINSID-$rid">>$FICHIER_CORRECTIONS_LDAP
                echo "">>$FICHIER_CORRECTIONS_LDAP
        fi
      done
      
      if [ "$SIMUL" == "1" ]; then
      	echo "La liste des modifications a effectuer a ete listee dans $FICHIER_CORRECTIONS_LDAP."
	echo "Aucune modification effectuee"
	echo
      else
      	ldapmodify -x -D $ADMINRDN,$BASEDN -w $ADMINPW -f $FICHIER_CORRECTIONS_LDAP
      	#~ rm $FICHIER_CORRECTIONS_LDAP
      	echo "La liste des modifications effectuee a ete listee dans $FICHIER_CORRECTIONS_LDAP."
      	echo "Annuaire corrige."
      	echo
      fi
    fi
else
    [ "$TESTSIDOPT" == "1" ] && exit 0  # TESTSID : pas d'erreur 
fi

############## Verification qu'il n'y a bien qu'un seul sambaDomainName ##################
test_ligne=`ldapsearch -xLLL objectClass=sambaDomain dn |grep dn |cut -d, -f -1 |cut -c 5- | wc -l`
if [ "$test_ligne" != "1" ]; then
	echo "ERREUR! Vous avez plus d'un nom de domaine:"
	LISTE=`ldapsearch -xLLL objectClass=sambaDomain dn |grep dn |cut -d, -f -1 |cut -c 5-`
        echo "$LISTE"
        DOMAINNAME=`cat /etc/samba/smb.conf | grep workgroup | sed -e "s/ //g" | cut -d"=" -f2`
        if [ "$SIMUL" == "1" ]; then
		echo "L'annuaire devrait etre corrige par rapport a votre smb.conf qui contient \"$DOMAINNAME\"."
		echo "Aucune modification effectuee"
		echo
	else
		echo "L'annuaire va etre corrige par rapport a votre smb.conf qui contient \"$DOMAINNAME\"."
		echo
	        if [ "$QUIET" != "1" ]; then
       		   POURSUIVRE
        	fi
	        echo "$LISTE" | grep -v "$DOMAINNAME" | while read A
        	do
          	echo $A,$BASEDN >> $REPSAVE/sambaDomainName_a_virer_$dateheure.ldif
        	done
        	ldapdelete -x -D $ADMINRDN,$BASEDN -w $ADMINPW -f $REPSAVE/sambaDomainName_a_virer_$dateheure.ldif
	fi
fi

####################### partie correction du mot de passe LDAP dans le secrets.tdb ##############################
if [ "$CORRECTADMINPWSAMBA" == "1" ]; then
  echo "Correction du mot de passe LDAP (ldap.secret) dans le secrets.tdb (cas de probleme de connexion generalise des clients)."
  echo "ATTENTION : Un redemarrage de samba est necessaire !!!"
  echo 
  if [ "1" != "$QUIET" ]; then
    POURSUIVRE
  fi
  smbpasswd -w $ADMINPW > /dev/null
  /etc/init.d/samba restart
  echo "Effectue."
fi

################### partie vide les cache de samba en cas de probl�me d'impression g�n�ralis� ou autre #####################
if [ "$VIDECACHE" == "1" ]; then
	echo "On vide les fichier tdb cache de samba"
	if [ "1" != "$QUIET" ]; then
		echo "ATTENTION : Un redemarrage de samba est necessaire !!!"
		echo 
		POURSUIVRE
	fi
	echo "Arret de samba"
	/etc/init.d/samba stop > /dev/null
	echo "On vide les tdb en cache."
	rm -f /var/cache/samba/*.tdb	
	rm -f /var/cache/samba/printing/*.tdb
	echo "Demarrage de samba"
	/etc/init.d/samba start > /dev/null
	echo "Effectue."
fi
# faut il lancer une correction de mdp root dans samba ?
#/usr/share/se3/scripts/change_root_smbpass.sh
