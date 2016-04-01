#!/bin/bash


# AUTEUR: Lacroix Olivier
# Fichier de filtrage du resultat de quota (initialement de repquota) pour gagner en rapidite: fortement inspire du script de Franck Molle quota.sh
#

## $Id: repquota_filtre.sh 9294 2016-03-28 23:23:50Z keyser $ ##

#
##### Affiche les quotas effectivement fixes sur une partition donnee pour toute ou partie des users #####
#

ERREUR()
{
echo
[ $1 == "1" ] && echo "ERREUR DE SYNTAXE:"
echo "Ce script n'admet comme premier argument que: /home ou /var/se3"
echo
echo "Exemples d'utilisations:"
echo
echo "repquota_filtre.sh /home Profs     affiche tous les quotas sur /home pour le groupe Profs"
echo
echo "repquota filtre.sh /home lacroixo  affiche le quota de l'utilisateur lacroixo"
echo
echo "repquota_filtre.sh /home           affiche tous les quotas sur /home pour tout l'annuaire"
exit $1
}

#ERREUR POUR L'INSTANT DANS LE TEST CI-DESSOUS: si pas d'argument, c'est pas gr!
[ $# -eq 0 ] && ERREUR 1
[ $# -gt 2 ] && ERREUR 1
[ "$1" = "--help" -o "$1" = "-h" ] && ERREUR 0
[ ! $1 == "/home" -a ! $1 == "/var/se3" ] && ERREUR 1
[ ! -e /usr/bin/quota ] && echo -e "Le paquet quota n'est pas installe.\nEffectuez:\n\tapt-get update\n\tapt-get install quota"

WWWPATH="/var/www"
fstype=$(grep  $1 /etc/mtab)
if $(echo $fstype | grep -q zfs); then
	partition=$(echo $fstype | awk '{ print $1 }')
	zfs=1
else
	partition=$(grep " $1 " /etc/mtab | cut -d " " -f1) #recherche des partitions reelles
	zfs=0
fi

. /etc/se3/config_l.cache.sh
BASEDN=$ldap_base_dn

# remplissage de $userliste : utilisateurs dont le quota doit etre affiche #####
if [ $# -eq 2 ] ; then #si 2 arguments, faire recherche
  #etablit la liste $userliste des utilisateurs a qui fixer le quota
  TST_GRP=$(ldapsearch -xLLL cn=$2 -b $BASEDN | grep member)
  if [ -z "$TST_GRP" ]; then
    TST_UID=$(ldapsearch -xLLL uid="$2")
    if [ -z "$TST_UID" ] ; then
      ERREUR "Impossible de trouver le groupe ou l'utilisateur passe en parametre dans l'annuaire Ldap"
    else
      userliste="$2"
    fi
  else
    userliste="$(ldapsearch -xLLL -b cn=$2,ou=Groups,$BASEDN memberUid | egrep "^memberUid:" |cut -d" " -f2)"
  fi
fi

#### affichage quota filtres ########
echo "Les utilisateurs non listes n'ont aucun fichier sur le disque."
echo "Ce n'est pas pour autant qu'ils n'ont pas de quota!"
echo
echo "L'unite est le Mo (occupation de l'espace disque arrondi a l'entier inferieur)."
echo
echo -e "Login\tUtilise\tQuota\tMax\tGrace"

if [ -z "$userliste" ] ; then
  # cas de l'absence du 2eme argument : on veut tous les utilisateurs ! $userliste est vide
  ##### on veut tout le monde #####
  motif="^[a-z]"
else
  ##### affichage filtre ####
  # dans la sortie de repquota, on ne garde que les lignes qui commencent par une minuscule (les seules concernant les utilisateurs) : egrep "^[a-z]"
  # je genere l'espression reguliere ^toto|^tata| avec la commande : echo "^$(echo ${userliste})" | sed "s/ /|^/g"
  motif="^$(echo ${userliste} | sed 's/ /|^/g')" 
fi

if [ "$zfs" == "1" ] ; then
	  /sbin/zfs userspace -H -p -o name,used,quota $partition | egrep "$motif" | egrep -v "[$]" | gawk -F" " '
    {
    used=int($2/1048576)
    if ($3 == "none")
    {
        quota="-"
	status="-"
	overfill="no"
    } else {
        quota=int($3/1048576)
        if (used+1 >= quota)
        {
	    status="Expire"
	    overfill="yes"
        } else {
            status="-"
	    overfill="no"
        }
    } 	
    print $1 "\t" used "\t" quota "\t" quota "\t" status "\t" overfill
    }'	 
else
   /usr/sbin/repquota $partition | egrep "$motif" | tr -s " " | sort -n | gawk -F" " '
    {
    $3/=1000
    $4/=1000
    gsub(/^0$/,"-",$4)
    gsub(/^0$/,"-",$5)
      {if ($2 == "+-") 
        {if ($6 == "none" || $6 == "aucun") 
          {print $1 "\t" $3 "\t" $4 "\t" $5 "\tExpire\tyes"
          }
          else 
          {if ($6 ~ ":") 
            {if (int($6) >= 24) 
              {print $1 "\t" $3 "\t" $4 "\t" $5 "\t2\tyes"
              } 
             else
              {print $1 "\t" $3 "\t" $4 "\t" $5 "\t1\tyes"
              }
            }
            else 
            gsub(/days/,"",$6)
            {print $1 "\t" $3 "\t" $4 "\t" $5 "\t" $6 "\tyes"
            }
          }
        } 
        else 
        {print $1 "\t" $3 "\t" $4 "\t" $5 "\t-\tno"
        }
      }
    }'

fi

