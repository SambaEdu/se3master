#!/bin/bash
# Auteurs: Olivier Lacroix
#
## $Id$ ##
#
##### script permettant de creer un message d avertissement a un user en depassement de quota #####
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
	echo -e "$0 n a pas besoin d argument pour fonctionner"
	echo -e "$COLINFO\c"
        echo "Exemples :"
	echo -e "$COLTXT"
        echo "warn_quota.sh  avertit les utilisateurs qui depassent leur quota sur /home et /var/se3 en les mettant dans le groupe overfill" 
        echo "(le template overfill possede une clef permettant l affichage d un message d avertissement au login)"
        echo
        echo "warn_quota.sh \"L:\ro\lynx\lynx.exe\"  avertit les utilisateurs depassant leur quota. L affichage se fait a l'aide du navigateur L:\ro\lynx\lynx.exe"
# 	echo -e "$COLTXT"
	exit 1
}

FICHIERLOCK=/tmp/warnquota.lock
FICHIEROVERFILL=/tmp/warnquota.overfill
REP_QUOTA=/usr/share/se3/scripts/repquota_filtre.sh

COMPL_OVERFILL()
{
    [ $1 == "/home" ] && disque=K
    [ $1 == "/var/se3" ] && disque=H
  
    # patch 1/2 pour affichage dans la page quota_visu des users en depassement
    rm /tmp/tmp_quota_$disque > /dev/null 2>&1
  
  # deux choses a faire :
  # 1. regarder si les personnes qui depassent leur quota sont dans overfill
  # 2. regarder si les personnes dans overfill ne devraient pas en sortir
  
  # 1.
 
    #filtre les lignes inutiles de repquota (debut), filtre le quota de root et de www-se3 non interessants pour se3 et trie par ordre alpha
    $REP_QUOTA  $1|grep 'yes$'|sort -t \t -k 1 |tr -s ' '| while read ligne 
    do
#      echo $ligne >> /tmp/tmp_quota_$disque

      #filtre les espaces superflus de chaque ligne, isole les champs et les arrondit
      nom=$(echo $ligne|cut -d " " -f1)
      utilise=$(echo $ligne|cut -d " " -f2)
      softquota=$(echo $ligne|cut -d " " -f3)
      hardquota=$(echo $ligne|cut -d " " -f4)
      grace=$(echo $ligne|cut  -d " " -f5)
      
      #patch 2/2 pour affichage dans la page quota_visu des users en depassement
      echo "$nom $utilise $softquota $hardquota $grace" |sed "s/ /\t/g"  >> /tmp/tmp_quota_$disque
    
      ismember_test=$(ldapsearch -xLLL "cn=overfill" | grep "^memberUid: ${nom}$" )
      # si l utilisateur n est pas encore dans overfill, on le rajoute, sinon, rien
      if [ -z "$ismember_test" ]; then
        /usr/share/se3/sbin/groupAddUser.pl $nom overfill
        echo "\"$nom\" vient d'etre ajoute dans overfill"
      fi
      # on enleve $nom de la liste $FICHIEROVERFILL a traiter pour le 2: resteront dans le fichier ceux a supprimer d'overfill
      sed -i $FICHIEROVERFILL -e "s/^$nom$//g"
    done #fin de la boucle 1.

}

#teste si 0 argument ou 1 egal au navigateur a utiliser pour les avertissements de depassement de quota
if [ $# -gt 1 -o "$1" = "--help" -o "$1" = "-h" ] ; then
  ERREUR
  exit 1
fi

WWWPATH="/var/www"
## recuperation des variables necessaires pour interroger mysql ###

. /etc/se3/config_o.cache.sh

# debut du script proprement dit

# la partition /home peut ne pas exister sur un backuppc ou slave
PASDEHOME=`cat /etc/mtab | grep /home`
  
if [ $# -eq 0 ] ; then
  
  if [ -e $FICHIERLOCK ]; then
    echo "Script déjà en cours d exécution"
    exit 1
  fi
  touch $FICHIERLOCK
  
  # creation si besoin d'overfill
  if [ "$(ldapsearch -xLLL "cn=overfill")" == "" ]; then
    /usr/share/se3/sbin/groupAdd.pl 1 overfill "Personnes dépassant leur quota d espace disque sur /home ou /var/se3."
    echo "Creation d'overfill (absent dans l'annuaire)."
  fi
  
  echo "Mise a jour du groupe overfill et du template correspondant..."
  
  # recuperation des partitions sur lesquelles il y a avertissement
  AVERT_HOME=`echo "select value from params where name='quota_warn_home'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
  AVERT_VARSE3=`echo "select value from params where name='quota_warn_varse3'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    
  # si les parametres n'existent pas , on les cree (une fois pour toutes)
  [ "$AVERT_HOME" == "" ] && echo "INSERT INTO params VALUES ('', 'quota_warn_home', '0', '0', 'Avertissement pour depassement de quota sur /home', '6')" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
  [ "$AVERT_VARSE3" == "" ] && echo "INSERT INTO params VALUES ('', 'quota_warn_varse3', '0', '0', 'Avertissement pour depassement de quota sur /var/se3', '6')" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
  
  # on remplit $FICHIEROVERFILL avec les utilisateurs d overfill a traiter : il faudra les enlever s'ils n'ont plus raison d'y etre (etape 2)
  ldapsearch -xLLL "cn=overfill" | grep ^memberUid | sed "s/memberUid: //g" > $FICHIEROVERFILL
  
  # 1. on remplit overfill avec ceux qui doivent y etre
  ### on remplit overfill pour les partitions sur lesquelles c'est parametre ####
  if [ "$AVERT_HOME" == "1" -a "$PASDEHOME" != "" ]; then
    COMPL_OVERFILL /home
  else
    echo "Les quotas sont inactifs pour la partition /home (ou elle n existe pas)... Aucune modification effectuée."
  fi
  if [ "$AVERT_VARSE3" == "1" ]; then
    COMPL_OVERFILL /var/se3
  else
    echo "Les quotas sont inactifs pour la partition /var/se3... Aucune modification effectuée."
  fi
  
  # 2. ceux qui etaient dans overfill et qui ne depassent plus le quota doivent sortir
  cat $FICHIEROVERFILL | grep "^[a-z]" | while read nom 
  do
      /usr/share/se3/sbin/groupDelUser.pl $nom overfill
      echo "$nom ne dépasse plus son quota : il vient d'être enlevé d'overfill"
  done # fin de la boucle 2.

  echo "Fin."
  rm $FICHIEROVERFILL
  # suppression fichier lock
  rm $FICHIERLOCK
fi

# a tous les lancements, on met a jour le template overfill : $URLINTERFACE pourrait changer (la crontab va actualiser)
if [ "$PASDEHOME" != "" ]; then
    # si /home existe alors
    echo "Mise a jour du navigateur pour les avertissements de dépassement..."
    
    BROWSERARG=$(echo $1 | sed 's!\\!/!g')
    URLINTERFACE=`echo "SELECT value FROM params WHERE name=\"urlse3\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    BROWSERSQL=`echo "SELECT value FROM params WHERE name=\"quota_browser\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
    
    # le navigateur impose est dans l ordre (si existe) celui donne en argument, dans mysql, sinon iexplore
    if [ -z "$BROWSERARG" ]; then
      if [ -z "$BROWSERSQL" ]; then
        BROWSER="iexplore"
      else
        BROWSER="$BROWSERSQL"
      fi
    else
      BROWSER="$BROWSERARG"
    fi
    
    #si un nouveau navigateur est impose dans $1 : on le met a jour ou on le rajoute dans mysql
    if [ $# -eq 1 ]; then
      if [ -n "$BROWSERSQL" ] ; then
        #~ echo "quota_browser EXISTE DANS LA BASE DE QUOTAS: MISE A JOUR EFFECTUEE"
        echo "UPDATE params SET value=\"$BROWSER\" WHERE name=\"quota_browser\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
      else
        #~ echo "quota_browser INEXISTANT DANS LA BASE DE QUOTAS: AJOUT DE CELUI CI"
        echo "INSERT INTO params VALUES ('','quota_browser','$BROWSER', '0','Navigateur affichant dépassements de quotas','6')" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
      fi
    fi
    
    ##### creation et parametrage du template overfill ####
    mkdir -p /home/templates/overfill
    echo "Effectuee."
else
    echo "Pas de partition /home sur ce serveur : pas d avertissement possible via les templates."
fi

