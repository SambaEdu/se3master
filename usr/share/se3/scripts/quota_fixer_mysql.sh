#!/bin/bash

# Auteur : Olivier Lacroix

## $Id$ ##



##### Met à jour, ajoute, supprime les références à un groupe (user) dans la table quotas de se3db puis recalcule le quota applicable à chacun #####
##### (le quota appliqué est le max des quotas applicables en fonction des groupes d'appartenance de user) #####


# Si pas de répertoire home on quitte (cas des se3 utilisés que pour la sauvegarde)
PASDEHOME=`cat /etc/fstab | grep /home`
if [ -z "$PASDEHOME" ]; then
    echo "Pas de dossier /home sur ce serveur"
    exit
fi


#Couleurs
COLTITRE="\033[1;35m"   # Rose
COLPARTIE="\033[1;34m"  # Bleu
COLTXT="\033[0;37m"     # Gris
COLCHOIX="\033[1;33m"   # Jaune
COLDEFAUT="\033[0;33m"  # Brun-jaune
COLSAISIE="\033[1;32m"  # Vert
COLCMD="\033[1;37m"     # Blanc
COLERREUR="\033[1;31m"  # Rouge
COLINFO="\033[0;36m"    # Cyan

AIDE()
{
echo -e "$COLERREUR\c"
echo -e "$0 a besoin d'arguments pour fonctionner"
echo -e "$COLINFO\c"
echo "UTILISATION 1: suppression de quota"
echo -e "$COLTXT\c"
echo "3 arguments: user_or_group , partition , suppr"
echo ""
echo -e "$COLSAISIE\c"
echo "ex: ./quota_fixer_mysql.sh Profs /home suppr"
echo "Supprime le quota fixé pour les Profs sur /home (le quota fixé dépendra alors des appartenances des users à d'autres groupes)"
echo ""
echo -e "$COLINFO\c"
echo "UTILISATION 2: actualisation des quotas (utile lors de la modification des groupes d'appartenance d'un user)"
echo -e "$COLTXT\c"
echo "3 arguments: user_or_group , partition , actu"
echo ""
echo -e "$COLSAISIE\c"
echo "ex: ./quota_fixer_mysql.sh hugov /var/se3 actu"
echo "Recalcule le quota effectivement applicable pour Victor Hugo en fonction de ses groupes d'appartenance"
echo ""
echo -e "$COLSAISIE\c"
echo "ex: ./quota_fixer_mysql.sh Toutlemonde Toutespartitions actu"
echo "Recalcule le quota effectivement applicable pour tous les utilisateurs de l annuaire sur /home et /var/se3"
echo ""
echo -e "$COLINFO\c"
echo "UTILISATION 3: ajout ou mise à jour de quotas"
echo -e "$COLTXT\c"
echo "Passer en arguments dans l'ordre :"
echo "- le nom du groupe ou de l'utilisateur dont vous voulez fixer le quota"
echo "- la partition sur laquelle on applique le quota"
echo "- le quota soft à fixer en Mo (ou 0 pour quota illimité affecté à cet user ou groupe)"
echo "- le quota hard à fixer en Mo."
echo -e "$COLSAISIE\c"
echo "ex1: ./quota_fixer_mysql.sh Profs /home 200 200"
echo "reglera le quota par defaut des Profs à 200Mo soft et hard sur home"
echo "ATTENTION: le quota effectivement attribué dépend aussi des autres appartenances à divers groupes."
echo "Si un prof appartient à un groupe ayant le droit à 300Mo soft, il aura 300Mo d'autorisation. "
echo ""
echo "ex2: ./quota_fixer_mysql.sh hugov /home 10 10"
echo "fixera un quota de 10Mo soft et hard sur home pour l'utilisateur hugov"
echo "ATTENTION: tout quota fixé sur un utilisateur particulier est prépondérant par rapport à ceux appliqués en fonction de l'appartenance aux groupes."
echo "Si hugov est un Prof et que le quota appliqué aux Profs est de 200Mo, hugov ne disposera que de 10Mo."
echo -e "$COLTXT\c"
exit 1
}

if [ "$1" = "--help" -o "$1" = "-h" ]
then
AIDE
fi

ERREUR()
{
echo -e "$COLERREUR"
echo "ERREUR!"
echo -e "$1"
echo -e "$COLTXT"
exit 1
}

#il faudrait tester plus finement les arguments passés :s
if [ $# -ne 4 -a $# -ne 3 ]; then
AIDE
fi

## recuperation des variables necessaires pour interoger mysql ###
WWWPATH="/var/www"
. /etc/se3/config_l.cache.sh
. /etc/se3/config_o.cache.sh
############# debut des fonctions ###############

QUOTA()
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


FIXERQUOTA()
{
part=$1
quotas=$2
quotah=$3

for user in $liste_users
do

#lance quota.sh pour fixer les quotas sur le système de fichiers pour chaque user de $liste_users apr�s avoir calcul� le quota applicable
#initialise la variable $indicegrp pour chaque user
indice_grp=0

#si user existe dans base mysql appliquer le quota de la base (QUOTA USER PREPONDERANT SUR CELUI DE TOUT GROUPE)
if [ -n "$(echo \"$test_exist_user\"|grep $user)" ]; then
  qsoft=`echo "SELECT quotasoft FROM quotas WHERE nom=\"$user\" AND type=\"u\" AND partition=\"$part\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
  qhard=`echo "SELECT quotahard FROM quotas WHERE nom=\"$user\" AND type=\"u\" AND partition=\"$part\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
else
#sinon, calcul du quota_max applicable à $user

#filtre les groupes auxquels appartient $user
#obtenir la liste $liste_quotas des quotas correspondants à la liste des groupes $liste_appartenance
liste_appartenance=""
liste_quotas=""
for grp in $liste_groupes
do
# 3 lignes suivantes a virer : plus de groupes utilisateur groupOfNames :
# test1=$(ldapsearch -x -LLL cn=$grp -b $BASEDN | grep uid | grep $user)
# test2=$(ldapsearch -x -LLL "cn=$grp" | grep memberUid | cut -d" " -f2 | grep $user)
# test_appartenance="$test1$test2"
test_appartenance=$(ldapsearch -xLLL -b $groupsRdn,$ldap_base_dn cn=$grp memberUid | grep " $user$")

if [ -n "$test_appartenance" ]; then
liste_appartenance="$liste_appartenance $grp"
quotasoft=`echo "SELECT quotasoft FROM quotas WHERE nom=\"$grp\" AND type=\"g\" AND partition=\"$part\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
liste_quotas="$liste_quotas $quotasoft"
fi
done


#déterminer le quota max applicable sur $liste_quotas
quota_max="1"
i=1 # à la place de 0 pour corriger bug d'apparition d'un espace au debut de $liste_appartenance
for quota in $liste_quotas
do
	i=$[$i+1]
	if [ $quota -eq 0 ]; then
		quota_max=0
		indice_grp=$i
	else
		if [ "$quota_max" -lt "$quota" -a $quota_max -ne 0 ]; then
		quota_max="$quota"
		indice_grp=$i
		fi
	fi
done


if [ $indice_grp -eq 0 ]; then
	#user n'appartient à aucun groupe dans la base mysql
	qsoft=0
	qhard=0

else #user appartient à un grp dans la base mysql
  #extraire les quotas soft et hard applicables
  groupe_preponderant=$(echo "$liste_appartenance" | cut -d " " -f$indice_grp)
  qsoft=`echo "SELECT quotasoft FROM quotas WHERE nom=\"$groupe_preponderant\" AND type=\"g\" AND partition=\"$part\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
  qhard=`echo "SELECT quotahard FROM quotas WHERE nom=\"$groupe_preponderant\" AND type=\"g\" AND partition=\"$part\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
fi
fi

# securite et correctif pour eviter un quota sur les comptes importants :
if [ "$user" = "admin" -o "$user" = "adminse3" -o "$user" = "root" -o "$user" = "www-se3" ]; then
	#user est un compte systeme : on imposte un quota 0. CORRECTIF du 26/09/10 pour effet retroactif sur le compte adminse3 qui n'etait pas protege.
	qsoft=0
	qhard=0
fi

echo "je fixe le quota pour $user sur la partition $part :"
echo "quota soft : $qsoft"
echo "quota hard : $qhard"
echo
# appliquer le quota à $user
# /usr/share/se3/scripts/quota.sh $user $[$qsoft*1000] $[$qhard*1000] $part
# GAIN DE PERF DE 1 A 4 EN COURCUITANT quota.sh
CREER_FICHIER $user $part
QUOTA $user $[$qsoft*1000] $[$qhard*1000] $part
done
}

CREER_FICHIER()
{
    if [ "$2" = "/home" ]; then
### protége les serveurs vmware (essentiellement) contre la création des homes de tous les users par création d'un fichier vide sur /home
      if [ ! -e /home/$1 ] ; then
        if [ ! -e /home/quotas_tmp ]; then
          mkdir /home/quotas_tmp
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
        chmod 700 /var/se3/quotas_tmp
      fi
      
      if [ ! -e /var/se3/quotas_tmp/$1 ]; then
        touch /var/se3/quotas_tmp/$1
        chown $1 /var/se3/quotas_tmp/$1
      fi
    fi
}
################### fin des fonctions #####################

################### début du script proprement dit #####################
user_grp=$1
partition=$2
quotas=$3
quotah=$4

[ "$partition" != "/home" -a "$partition" != "/var/se3" -a "$partition" != "Toutespartitions" ] && exit

#creation de la liste des users pour lesquels il faut refixer les quotas: $liste_users
if [ "$user_grp" = "Toutlemonde" ] ; then
  
  #~ liste_users=$(ldapsearch -x -b $peopleRdn,$ldap_base_dn uid | grep "^dn: " | cut -d, -f1 | cut -d= -f2)
  liste_users=$(ldapsearch -x -b $peopleRdn,$ldap_base_dn uid | grep "^uid: " | cut -d" " -f2 | grep -v "^admin$" | grep -v "^www-se3$" | grep -v "^root$" )
  #~ type="g"
  if [ "$2" = "Toutespartitions" ]; then
    partition=/home
    test_exist_user=`echo "SELECT nom FROM quotas WHERE type=\"u\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    #liste les groupes qui ont encore un quota affecté après les changements -> on regardera si $user appartient à chacun d'eux
    liste_groupes=`echo "SELECT nom FROM quotas WHERE type=\"g\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    FIXERQUOTA /home $quotas $quotah
    partition=/var/se3
    test_exist_user=`echo "SELECT nom FROM quotas WHERE type=\"u\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    #liste les groupes qui ont encore un quota affecté après les changements -> on regardera si $user appartient à chacun d'eux
    liste_groupes=`echo "SELECT nom FROM quotas WHERE type=\"g\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    FIXERQUOTA $partition $quotas $quotah
  else
    test_exist_user=`echo "SELECT nom FROM quotas WHERE type=\"u\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    #liste les groupes qui ont encore un quota affecté après les changements -> on regardera si $user appartient à chacun d'eux
    liste_groupes=`echo "SELECT nom FROM quotas WHERE type=\"g\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    
    FIXERQUOTA $partition $quotas $quotah
  fi
else
  TST_GRP=$(ldapsearch -xLLL "cn=$1" -b $groupsRdn,$ldap_base_dn)
  if [ -z "$TST_GRP" ]; then
          TST_UID=$(ldapsearch -xLLL uid="$1")
          if [ -z "$TST_UID" ]; then
            echo "Impossible de trouver le groupe ou l'utilisateur passé en paramètre dans l'annuaire Ldap"
	    ERREURFLAG=1
          else
            #c'est un user
            liste_users=$1
            type="u"
          fi
  else
          #c'est un groupe: on liste les users du groupe
          type="g"
          TST_GRP_POSIX=$(ldapsearch -xLLL "cn=$1" -b  $groupsRdn,$ldap_base_dn | grep memberUid)
          #echo "Liste groupe: $TST_GRP_POSIX"
          if [ -z "$TST_GRP_POSIX" ]; then
            liste_users=$(ldapsearch -x -LLL cn=$1 -b $ldap_base_dn | grep uid | cut -d " " -f2 |  cut -d "=" -f2 | cut -d "," -f1)
          else
	    TST_GRP_VIDE=$(ldapsearch -xLLL "cn=$1" -b  $groupsRdn,$ldap_base_dn | grep member)
	    if [ -z "$TST_GRP_VIDE" ]; then
		echo "Le groupe passe en argument est vide."
	    else
		liste_users=$(ldapsearch -x -LLL "cn=$1" | grep memberUid | cut -d " " -f2)
	    fi
          fi
  fi

  # efface le groupe ou user de la base si demandé (APRES AVOIR LISTE LES USERS CONCERNES)
  if [ "$3" = "suppr" ]; then
          echo "SUPPRESSION DES QUOTAS SUR $user_grp: RECALCUL DES QUOTAS EN FONCTION DES APPARTENANCES A D'AUTRES GROUPES."

	# PATCH pour suppression des users-groupes ayant disparu de l'annuaire : on supprime toute référence de tout "type" dans la table.
	if [ "$ERREURFLAG" = "1" ]; then
		`echo "DELETE FROM quotas WHERE nom=\"$user_grp\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	else
		# dans ce cas, on sait si type=u ou g, on supprime uniquement l'entrée correspondante. (un groupe et un utilisateur peuvent avoir le meme nom)
		`echo "DELETE FROM quotas WHERE nom=\"$user_grp\" AND type=\"$type\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
	fi
  fi

# PATCH pour suppression des users-groupes ayant disparu de l'annuaire
[ "$ERREURFLAG" = "1" ] && echo ERREUR "Sortie."  

  # on empeche les betises !
  liste_users="$(echo "$liste_users" | grep -v "^admin$" | grep -v "^adminse3$" | grep -v "^www-se3$" | grep -v "^root$" )"
  
  # complete mysql ou mise à jour suivant le cas.
  if [ "$3" != "suppr" -a "$3" != "actu" ]; then
          test_exist=`echo "SELECT nom FROM quotas WHERE nom=\"$user_grp\" AND type=\"$type\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
          # regarde s'il s'agit d'un update ou d'une insert
          if [ -n "$test_exist" ]; then
            echo "$user_grp EXISTE DANS LA BASE DE QUOTAS: MISE A JOUR EFFECTUEE"
            $(`echo "UPDATE quotas SET quotasoft=$quotas, quotahard=$quotah WHERE nom=\"$user_grp\" AND type=\"$type\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`)
          else
            echo "$user_grp INEXISTANT DANS LA BASE DE QUOTAS: AJOUT DE CELUI CI ET RECALCUL DES QUOTAS DE SES UTILISATEURS"
            echo "INSERT INTO quotas VALUES ('$type','$user_grp',$quotas, $quotah,'$partition')" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
          fi
  fi
  
  #certaines requetes mysql et LDAP placées avant la boucle for qui suit pour éviter de les refaire inutilement
  #liste les users qui sont dans la base: leur quota sera prépondérant sur tout quota appliqué à leurs groupes
  #test_exist_user=`echo "SELECT nom FROM quotas WHERE type=\"u\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
  #liste les groupes qui ont encore un quota affecté après les changements -> on regardera si $user appartient à chacun d'eux
  #liste_groupes=`echo "SELECT nom FROM quotas WHERE type=\"g\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
  
  if [ "$2" = "Toutespartitions" ]; then
    partition=/home
    test_exist_user=`echo "SELECT nom FROM quotas WHERE type=\"u\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    #liste les groupes qui ont encore un quota affecté après les changements -> on regardera si $user appartient à chacun d'eux
    liste_groupes=`echo "SELECT nom FROM quotas WHERE type=\"g\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    FIXERQUOTA /home $quotas $quotah
    partition=/var/se3
    test_exist_user=`echo "SELECT nom FROM quotas WHERE type=\"u\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    #liste les groupes qui ont encore un quota affecté après les changements -> on regardera si $user appartient à chacun d'eux
    liste_groupes=`echo "SELECT nom FROM quotas WHERE type=\"g\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    FIXERQUOTA $partition $quotas $quotah
  else
    test_exist_user=`echo "SELECT nom FROM quotas WHERE type=\"u\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    #liste les groupes qui ont encore un quota affecté après les changements -> on regardera si $user appartient � chacun d'eux
    liste_groupes=`echo "SELECT nom FROM quotas WHERE type=\"g\" AND partition=\"$partition\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    
    FIXERQUOTA $partition $quotas $quotah
  fi
fi

rm -R /home/quotas_tmp > /dev/null 2>&1
rm -R /var/se3/quotas_tmp > /dev/null 2>&1
############# fin du script #####################

