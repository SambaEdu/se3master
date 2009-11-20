#!/bin/bash
# AUTEUR: Lacroix Olivier
# version 1.0
# Fichier de filtrage du resultat de quota (initialement de repquota) pour gagner en rapidite: fortement inspire du script de Franck Molle quota.sh
#
## $Id$ ##
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
partition=$(grep " $1 " /etc/mtab | cut -d " " -f1) #recherche des partitions reelles

## recuperation des variables necessaires pour interoger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
  dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f2 | cut -d \" -f2`
  dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
  dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
  dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
  ERREUR "Fichier de configuration inaccessible, le script ne peut se poursuivre."
fi

### recuperation des parametres actuels de l'annuaire dans la base ####
BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

#### remplissage de $userliste : utilisateurs dont le quota doit etre affiche #####
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
    userliste="$(ldapsearch -xLLL -b cn=$2,ou=Groups,$BASEDN memberUid | egrep "^memberUid:" | cut -d" " -f2)"
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
  repquota $partition | egrep "^[a-z]" | tr -s " " | sort -n | gawk -F" " '
    {
    $3/=1000
    $4/=1000
    gsub(/^0$/,"-",$4)
    gsub(/^0$/,"-",$5)
      {if ($2 == "+-") 
        {if ($6 == "none" || $6 == "aucun") 
          {print $1 "\t" $3 "\t" $4 "\t" $5 "\tExpire"
          }
          else 
          {if ($6 ~ ":") 
            {if (int($6) >= 24) 
              {print $1 "\t" $3 "\t" $4 "\t" $5 "\t2"
              } 
             else
              {print $1 "\t" $3 "\t" $4 "\t" $5 "\t1"
              }
            }
            else 
            gsub(/days/,"",$6)
            {print $1 "\t" $3 "\t" $4 "\t" $5 "\t" $6
            }
          }
        } 
        else 
        {print $1 "\t" $3 "\t" $4 "\t" $5 "\t-"
        }
      }
    }'
else
  ##### affichage filtre ####
  # dans la sortie de repquota, on ne garde que les lignes qui commencent par une minuscule (les seules concernant les utilisateurs) : egrep "^[a-z]"
  # je genere l'espression reguliere ^toto|^tata| avec la commande : echo "^$(echo ${userliste})" | sed "s/ /|^/g"
  repquota $partition | egrep "^[a-z]" | egrep "`echo "^$(echo ${userliste})" | sed "s/ /|^/g"`" | tr -s " " | sort -n | gawk -F" " '
    {
    $3/=1000
    $4/=1000
    gsub(/^0$/,"-",$4)
    gsub(/^0$/,"-",$5)
      {if ($2 == "+-") 
        {if ($6 == "none" || $6 =="aucun") 
          {print $1 "\t" $3 "\t" $4 "\t" $5 "\tExpire"
          }
          else 
          {if ($6 ~ ":") 
            {if (int($6) >= 24) 
              {print $1 "\t" $3 "\t" $4 "\t" $5 "\t2"
              } 
             else
              {print $1 "\t" $3 "\t" $4 "\t" $5 "\t1"
              }
            }
            else 
            gsub(/days/,"",$6)
            {print $1 "\t" $3 "\t" $4 "\t" $5 "\t" $6
            }
          }
        } 
        else 
        {print $1 "\t" $3 "\t" $4 "\t" $5 "\t-"
        }
      }
    }'
fi
