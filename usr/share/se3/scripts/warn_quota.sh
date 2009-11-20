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

grep xfs /etc/fstab >/dev/null
if [ "$?" == "0" ]
then
        REP_QUOTA="/usr/sbin/repquota -F xfs"
else
        REP_QUOTA="/usr/sbin/repquota"
fi


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
    $REP_QUOTA -v $1|grep '+-'|grep -v root|grep -v www-se3|sort -t \t -k 1 | while read ligne 
    do
      #filtre les espaces superflus de chaque ligne, isole les champs et les arrondit
      nom=$(echo $ligne|tr -s " "|cut -d " " -f1)
      utilise=$(($(echo $ligne|tr -s " "|cut -d " " -f3)/1000))
      softquota=$(($(echo $ligne|tr -s " "|cut -d " " -f4)/1000))
      hardquota=$(($(echo $ligne|tr -s " "|cut -d " " -f5)/1000))
      grace=$(echo $ligne|tr -s " "|cut -d " " -f6)
    
      #on sait que grace est non vide d'apres le grep +-
      #filtrage du cas delai < 48:00
      if [ "$grace" == "none" -o "$grace" == "aucun" ] ; then
        grace="Expire"
      else
        if [ -n "$(echo $grace|grep ":")" ] ; then
      #il faut filtrer car la grace est au format H:min
          nbreh=$(echo $grace|cut -d ":" -f1|sed -e "s/ //g")
          grace="1"
          [ "$nbreh" -lt 24 ] && grace="0"
        else
          grace=$(echo $grace | sed "s/days//" )
        fi
      fi
      #~ echo "$nom $utilise $softquota $grace" 
      
      #patch 2/2 pour affichage dans la page quota_visu des users en depassement
      echo "$nom $utilise $softquota $hardquota $grace"| sed -e "s/ /\t/g" >> /tmp/tmp_quota_$disque
    
      ismember_test=$(cat $FICHIEROVERFILL | grep "^$nom$" )
      # si l utilisateur n est pas encore dans overfill, on le rajoute, sinon, rien
      if [ -z "$ismember_test" ]; then
        /usr/share/se3/sbin/groupAddUser.pl $nom overfill
        echo "$nom vient d'etre ajoute dans overfill"
      fi
      # on enleve $nom de la liste $FICHIEROVERFILL a traiter pour le 2.
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
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
  dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f2 | cut -d \" -f2`
  dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut -d = -f 2 |cut -d \" -f 2`
  dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut -d = -f 2 | cut -d \" -f 2`
  dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut -d = -f 2 | cut -d \" -f 2`
else
  echo -e "$COLERREUR"
  echo "Fichier de configuration mysql inaccessible, le script ne peut se poursuivre."
  exit 1
fi

# debut du script proprement dit

# la partition /home peut ne pas exister sur un backuppc ou slave
PASDEHOME=`cat /etc/fstab | grep /home`
  
if [ $# -eq 0 ] ; then
  
  if [ -e $FICHIERLOCK ]; then
    echo "Script deja en cours d execution"
    exit 1
  fi
  touch $FICHIERLOCK
  
  # creation si besoin d'overfill
  if [ "$(ldapsearch -xLLL "cn=overfill")" == "" ]; then
    /usr/share/se3/sbin/groupAdd.pl 1 overfill "Personnes depassant leur quota d espace disque sur /home ou /var/se3."
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
    echo "Les quotas sont inactifs pour la partition /home (ou elle n existe pas)... Aucune modification effectuee."
  fi
  if [ "$AVERT_VARSE3" == "1" ]; then
    COMPL_OVERFILL /var/se3
  else
    echo "Les quotas sont inactifs pour la partition /var/se3... Aucune modification effectuee."
  fi
  
  # 2. ceux qui etaient dans overfill et qui ne depassent plus le quota doivent sortir
  cat $FICHIEROVERFILL | grep "^[a-z]" | while read nom 
  do
      /usr/share/se3/sbin/groupDelUser.pl $nom overfill
      echo "$nom ne depasse plus son quota : il vient d'etre enleve d'overfill"
  done # fin de la boucle 2.

  echo "Fin."
  rm $FICHIEROVERFILL
  # suppression fichier lock
  rm $FICHIERLOCK
fi

# a tous les lancements, on met a jour le template overfill : $URLINTERFACE pourrait changer (la crontab va actualiser)
if [ "$PASDEHOME" != "" ]; then
    # si /home existe alors
    echo "Mise a jour du navigateur pour les avertissements de depassement..."
    
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
        echo "INSERT INTO params VALUES ('','quota_browser','$BROWSER', '0','Navigateur affichant depassements de quotas','6')" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N
      fi
    fi
    
    ##### creation et parametrage du template overfill ####
    mkdir -p /home/templates/overfill
    #nettoyage du template (utile en cas de chgt de l'adresse urlse3)
    if [ -e /home/templates/overfill/registre.zrn ]; then
      sed -i /home/templates/overfill/registre.zrn -e "s!WarnQuota @@@!####delete me####!"
      sed -i /home/templates/overfill/registre.zrn -e "/####delete me####/d"
    else
      echo "#overfill (ajout automatique par warnquota.sh)" > /home/templates/overfill/registre.zrn
    fi
    chown -R "www-se3"  /home/templates/overfill #pour pouvoir rajouter des clefs sur overfill via l'interface
    
    # dans mysql, le chemin est stocke avec des / (L:/ro/lynx/lynx.exe), pour windows il faut des \
    #transforme les / stockes ds mysql en \ pour le chemin windows + correction bug \r, \f, \n, \t et \e mals pris en compte
    BROWSERWIN=$(echo $BROWSER | sed 's!/r!\\\\r!g' | sed 's!/n!\\\\n!g' | sed 's!/f!\\\\f!g' | sed 's!/t!\\\\t!g' | sed 's!/e!\\\\e!g' | sed 's!/!\\!g' )
    
    # la cle n existe pas puisqu on l a supprime avant
    #~ CLEEXIST="$(grep "WarnQuota @@@" /home/templates/overfill/registre.zrn)"
    #~ if [ -z "$CLEEXIST" ] ; then
    echo -e "TOUS @@@ ADD @@@  HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Run\\WarnQuota @@@ $BROWSERWIN $URLINTERFACE @@@ REG_SZ \r" >> /home/templates/overfill/registre.zrn
        #~ chown "www-se3" /home/templates/overfill/registre.zrn
    #~ fi
    
    # il faut supprimer la cle du ntuser.dat si elle a ete rajoutee et que l utilisateur a quitte overfill
    # si la cle n est pas supprimee dans base, on rajoute cette suppression
    mkdir -p /home/templates/base
    if [ ! -e /home/templates/base/registre.zrn ]; then
      echo "#base (ajout automatique par warnquota.sh)" > /home/templates/base/registre.zrn
    fi
    chown -R "www-se3"  /home/templates/base #pour pouvoir rajouter des clefs sur base via l'interface
    
    CLEEXIST="$(grep "WarnQuota " /home/templates/base/registre.zrn)"
    if [ -z "$CLEEXIST" ] ; then
      echo -e "TOUS @@@ DEL @@@ HKEY_CURRENT_USER\\Software\\Microsoft\\Windows\\CurrentVersion\\Run\\WarnQuota \r" >> /home/templates/base/registre.zrn
      #~ chown "www-se3" /home/templates/base/registre.zrn
    fi
    echo "Effectuee."
else
    echo "Pas de partition /home sur ce serveur : pas d avertissement possible via les templates."
fi

