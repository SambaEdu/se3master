#!/bin/bash
#
## $Id$ ##
#
##### Script permettant de modifier l'annuaire pour compatibilite debian Etch ##### 

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script permettant de modifier l'annuaire pour le rendre compatible avec Debian Etch (schemacheck on)"
	echo "Usage : Aucune option"
	exit
fi	

OPT="$1"

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
POURSUIVRE()
{
	REPONSE=""
	while [ "$REPONSE" != "o" -a "$REPONSE" != "n" ]
	do
		echo -e "$COLTXT"
		echo -e "Peut-on poursuivre? (${COLCHOIX}O/n${COLTXT}) $COLSAISIE\c"
		read REPONSE
		if [ -z "$REPONSE" ]; then
			REPONSE="o"
		fi
	done

	if [ "$REPONSE" != "o" -a "$REPONSE" != "O" ]; then
		ERREUR "Abandon!"
	fi
}

REPORT_FILE="/root/mailtoadmin"
MAIL_REPORT()
{
[ -e /etc/ssmtp/ssmtp.conf ] && MAIL_ADMIN=$(cat /etc/ssmtp/ssmtp.conf | grep root | cut -d= -f2)
if [ ! -z "$MAIL_ADMIN" ]; then
	REPORT=$(cat $REPORT_FILE)
	#On envoie un mail a l'admin
	echo "$REPORT"  | mail -s "[SE3] Erreurs constatées sur $0" $MAIL_ADMIN
fi
}



if [ "$2" != "noverif" ]; then
	echo -e "$COLINFO"
	echo "Vérification en ligne que vous avez bien la dernière version des scripts de migration"
	echo -e "$COLTXT"
	cd /root
	ARCHIVE_FILE="migration_sarge2etch.tgz"
	ARCHIVE_FILE_MD5="migration_sarge2etch.md5"
	SCRIPTS_DIR="/usr/share/se3/sbin"
	
	rm -f $ARCHIVE_FILE_MD5 $ARCHIVE_FILE
	wget -N -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr/majse3/$ARCHIVE_FILE
	wget -N -q --tries=1 --connect-timeout=1 http://wawadeb.crdp.ac-caen.fr/majse3/$ARCHIVE_FILE_MD5
	MD5_CTRL=$(cat $ARCHIVE_FILE_MD5)
	MD5_CTRL_LOCAL=$(md5sum $ARCHIVE_FILE)
	if [ "$MD5_CTRL" != "$MD5_CTRL_LOCAL" ]
	then	
		echo -e "$COLERREUR"
		echo "Controle MD5 de l'archive incorrecte, relancez le script afin qu'elle soit de nouveau téléchargée"
		echo -e "$COLTXT"
		exit 1
	fi

	tar -xzf $ARCHIVE_FILE
	cd $SCRIPTS_DIR
	MD5_CTRL_LOCAL1=$(md5sum se3_upgrade_etch.sh)
	MD5_CTRL_LOCAL2=$(md5sum migration_ldap_etch.sh)
	# MD5_CTRL_LOCAL3=$(md5sum migration_UTF8.sh)
	cd -
	MD5_CTRL1=$(cat se3_upgrade_etch.md5)
	MD5_CTRL2=$(cat migration_ldap_etch.md5)
	# MD5_CTRL3=$(cat migration_UTF8.md5)
	chmod +x *.sh

	[ "$MD5_CTRL1" != "$MD5_CTRL_LOCAL1" ] && RELANCE="YES" && cp se3_upgrade_etch.sh $SCRIPTS_DIR/
	[ "$MD5_CTRL2" != "$MD5_CTRL_LOCAL2" ] && cp migration_ldap_etch.sh $SCRIPTS_DIR/
	# [ "$MD5_CTRL3" != "$MD5_CTRL_LOCAL3" ] && cp migration_UTF8.sh $SCRIPTS_DIR/
	if [ "$RELANCE" == "YES" ]
	then
		echo -e "$COLINFO"
		echo "Les scripts de migration ont été mis à jour depuis le serveur central, veuiller relancer $0"
		echo "afin de prendre en compte les changements"
		exit 1
		echo -e "$COLTXT"
	
	
	fi
	echo -e "$COLINFO"
	echo "Vous disposez de la dernière version des scritps de migration, la migration peut se poursuivre..."
	sleep 2
	echo -e "$COLTXT"
fi





### on suppose que l'on est sous debian ;) ####
WWWPATH="/var/www"
DATE=$(date +%d-%m-%Y)
REP_EXPORT="/var/se3/save/migration_etch"

#clear
echo -e "$COLTITRE"
echo "*********************************************"
echo "*  ADAPTATION DE L'ANNUAIRE LDAP POUR ETCH  *"
echo "*********************************************"
echo -e "$COLTXT"
#echo "Appuyez sur Entree pour continuer........"
#echo -e "$COLTXT"
#read pause





## recuperation des variables necessaires pour interoger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut	-d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut	-d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut	-d = -f 2 |cut -d \" -f 2`
else
	echo "Fichier de conf inaccessible !!"
	echo "le script ne peut se poursuivre"
	exit 1
fi


IP_LOCAL=`ifconfig | grep Bcast |cut -d":" -f2| cut -d" " -f1`
LDAP_SERVER=`echo "SELECT value FROM params WHERE name=\"ldap_server\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

if [ "$IP_LOCAL" != "$LDAP_SERVER" ]
then
        echo "ATTENTION : Serveur LDAP déporté"
        echo "La modification de l'annuaire ne peut se faire que sur un annuaire local"
        exit
fi

LDAP_REPLICA=`echo "SELECT value FROM params WHERE name=\"replica_status\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

if [ "$LDAP_REPLICA" = "2" -o "$LDAP_REPLICA" = "4" ]
then
        echo "ATTENTION : Serveur LDAP esclave"
        echo "La modification doit etre realisee sur le serveur maitre"
        exit
fi

if [ "$LDAP_REPLICA" = "1" -o "$LDAP_REPLICA" = "3" ]
then
        echo "ATTENTION : Serveur LDAP répliqué sur un serveur esclave"
        echo "Si le serveur esclave est de type Lcs 1.x sous sarge, vous ne pourrez plus creer / modifier les comptes depuis" 
	echo "le lcs jusqu'a ce qu'il migre lui meme en version 2.0 sous Etch"
        POURSUIVRE
fi


PASSLDAP=$(cat /etc/ldap.secret)
ROOTDN=$(cat /etc/ldap/slapd.conf | grep rootdn | cut -f3 | sed -e s/\"//g)


### recuperation des parametres actuels de l'annuaire dans la base ####
BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
ADMLDAP=`echo "SELECT value FROM params WHERE name=\"adminRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
PASSLDAP=`echo "SELECT value FROM params WHERE name=\"adminPw\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
DN_BASEDN=`echo $BASEDN | cut -d',' -f1`
DN_TYPE=`echo $DN_BASEDN | cut -d = -f1` 
DN_TYPE_2=`echo $DN_BASEDN | cut -d = -f2` 

COMPUTERSRDN=`echo "SELECT value FROM params WHERE name=\"computersRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
PEOPLERDN=`echo "SELECT value FROM params WHERE name=\"peopleRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
RIGHTSRDN=`echo "SELECT value FROM params WHERE name=\"rightsRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
PARCSRDN=`echo "SELECT value FROM params WHERE name=\"parcsRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
GROUPSRDN=`echo "SELECT value FROM params WHERE name=\"groupsRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
TRASHRDN=`echo "SELECT value FROM params WHERE name=\"trashRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
ADMINRDN=`echo "SELECT value FROM params WHERE name='adminRdn'" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

/etc/init.d/slapd start > /dev/null
TEST_SLAPD="$(ps -aux 2>/dev/null | grep slapd | grep -v grep)"
if [ "$TEST_SLAPD" == "" ]; then
  echo -e "$COLTITRE"
  echo "Votre annuaire LDAP ne peut etre demarre. La conversion de votre annuaire au nouveau format ne pourra etre effectuee normalement."
  echo -e "$COLTXT"
  echo "Appuyez sur une touche pour continuer..."
  read pause
fi

# On recupere les anciennes entrees
ldapsearch -xLLL -D $ADMLDAP,$BASEDN -w $PASSLDAP objectCLass=sambaAccount > /tmp/cnvrt_old.ldif
TSTOLDUSERS=$(cat "/tmp/cnvrt_old.ldif")

if [ "$TSTOLDUSERS" != "" ]; then

# On convertit le ldif
/usr/share/se3/sbin/convertSambaAccount --input /tmp/cnvrt_old.ldif --output /tmp/cnvrt_mod.ldif --sid $DOMAINSID --changetype modify

# On modifie les nouvelles entrees
ldapmodify -x -c -D $ADMLDAP,$BASEDN -w $PASSLDAP -f /tmp/cnvrt_mod.ldif
fi

echo -e "$COLCMD"
echo "Patientez exportation sauvegarde l'annuaire en cours..."
echo "La sauvegarde est réalisée dans $REP_EXPORT"
echo "ATTENTION : Cette operation peut etre assez longue suivant les cas."
echo "NE SURTOUT PAS L'INTERROMPRE"
echo -e "$COLTXT"

if [ ! -d $REP_EXPORT ]
then
	mkdir $REP_EXPORT
fi	

echo "Sauvegarde de l'annuaire...."
slapcat > $REP_EXPORT/export_ldap.ldif

#echo "Appuyez sur Entree pour continuer........"
#read pause

if [ -e /usr/share/se3/sbin/gon2posix.sh ]
then
	echo "Transformation des GoN en posix groups dans la branche Groups"
	/usr/share/se3/sbin/gon2posix.sh
fi

echo -e "$COLCMD"
echo "Début du nettoyage....."
echo -e "$COLTXT"

# Verif du SID
echo "Vérification du SID..."
if [ "$(/usr/share/se3/scripts/testSID.sh)" != "" ]; then
echo -e "$COLCMD"
echo "Correction du SID..."
echo -e "$COLTXT"
/usr/share/se3/scripts/correctSID.sh
fi

# Supprime le compte ldapadm
echo "Suppression s'il existe du compte ldapadm ...."
ldapdelete -x -v -D "$ROOTDN" -w "$PASSLDAP" "uid=ldapadm,$PEOPLERDN,$BASEDN" > /dev/null 2>&1
ldapdelete -x -v -D "$ROOTDN" -w "$PASSLDAP" "uid=root,$BASEDN" > /dev/null 2>&1
ldapdelete -x -v -D "$ROOTDN" -w "$PASSLDAP" "uid=nobody,$TRASHRDN,$BASEDN" > /dev/null 2>&1

# Ajoute les entrees manquantes a la base dn
echo "Mise en conformité de la BaseDn ...."
echo "Type de base $DN_TYPE"
if [ "$DN_TYPE" = "ou" ]
then
	OBJECT_CLASS="organizationalUnit"
fi

if [ "$DN_TYPE" = "dc" ]
then
	OBJECT_CLASS="Domain"
	
fi


if [ "$DN_TYPE" = "dc" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF 
dn: $BASEDN
changetype: modify
delete: objectClass
objectClass: dcObject
EOF

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF 
dn: $BASEDN
changetype: modify
delete: objectClass
objectClass: organization
EOF

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF 
dn: $BASEDN
changetype: modify
delete: o
EOF
fi

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF 
dn: $BASEDN
changetype: modify
add: objectClass
objectClass: $OBJECT_CLASS
EOF

## modif wawa

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF 
dn: $BASEDN
changetype: modify
delete: $DN_TYPE
dc: sambaedu
EOF

## /modif wawa

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF 
dn: $BASEDN
changetype: modify
add: $DN_TYPE
$DN_TYPE: $DN_TYPE_2
EOF

# Ajoute attribut sn: SE3 pour admin 
TEST_SN_ADMIN=`ldapsearch -x -b $PEOPLERDN,$BASEDN '(&(sn=SE3)(uid=admin))' sn |grep sn: >/dev/null 2>&1 && echo 1`
if [ "$TEST_SN_ADMIN" != "1" ] 
then
ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF
dn: uid=admin,$PEOPLERDN,$BASEDN
changetype: modify
add: sn
sn: SE3
EOF
fi

# Ajoute l'objectClass manquant au machine
echo "Mise en conformite des objets machines dans l'annuaire..."
ldapsearch  -x -b $COMPUTERSRDN,$BASEDN '(objectclass=ipHost)' cn | grep -v \# | grep cn:  | while read A
do

CN=`echo $A | cut -d : -f 2`

if [ "$CN" != "" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF
dn: cn=$CN,$COMPUTERSRDN,$BASEDN
changetype: modify
add: objectClass
objectClass: organizationalRole
EOF

fi

done

# Dans certain cas quelques comptes user ont un objectClass account en trop.
# On le vire
echo "Controle et modification si necessaire des objets user dans la branche People..."
ldapsearch  -x -b $PEOPLERDN,$BASEDN '(objectclass=account)' uid | grep -v \# | grep uid:  | while read A
do

ID=`echo $A | cut -d : -f 2`

if [ "$ID" != "" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1  <<EOF
dn: uid=$ID,$PEOPLERDN,$BASEDN
changetype: modify
delete: objectClass
objectClass: account
EOF

fi

done

# Modif wawa
# le groupe lcs-users a un objectClass organizationalUnit en trop.
# On le vire
echo "Controle et modification si necessaire des objets lcs-users dans la branche Group..."
ldapsearch  -x -b $GROUPSRDN,$BASEDN '(objectclass=organizationalUnit)' cn | grep -v \# | grep cn:  | while read A
do

ID=`echo $A | cut -d : -f 2`

if [ "$ID" != "" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1  <<EOF
dn: cn=$ID,$GROUPSRDN,$BASEDN
changetype: modify
delete: objectClass
objectClass: organizationalUnit
EOF

fi

done
#/Modif wawa

# Dans certain cas quelques comptes user ont un objectClass account en trop. (DANS TRASHRDN aussi)
# On le vire
echo "Controle et modification si nécessaire des objets user dans la branche People..."
ldapsearch  -x -b $TRASHRDN,$BASEDN '(objectclass=account)' uid | grep -v \# | grep uid:  | while read A
do

ID=`echo $A | cut -d : -f 2`

if [ "$ID" != "" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1  <<EOF
dn: uid=$ID,$TRASHRDN,$BASEDN
changetype: modify
delete: objectClass
objectClass: account
EOF

fi

done

## Modif simc
# Dans certains cas les equipes on un owner.
# On le vire
echo "Controle et modification si necessaire des Groups..."
ldapsearch  -x -b $GROUPSRDN,$BASEDN '(objectclass=posixGroup)' cn | grep -v \# | grep ^cn  | while read A
do

GROUP=`echo $A | cut -d : -f 2`

if [ "$GROUP" != "" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1  <<EOF
dn: cn=$GROUP,$GROUPSRDN,$BASEDN
changetype: modify
delete: owner

EOF

fi

done
##/Modif simc

# Certains comptes ont un attribut AcctFlag (samba2) On supprime
echo "Suppression de AccFlags de la branche Trash"
ldapsearch  -x -b $TRASHRDN,$BASEDN '(acctflags=*)' uid | grep uid:  | while read A
do

ID=`echo $A | cut -d : -f 2`

if [ "$ID" != "" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1  <<EOF
dn: uid=$ID,$TRASHRDN,$BASEDN
changetype: modify
delete: acctflags
EOF

fi

done


# Certains comptes ont un attribut AcctFlag (samba2) On supprime
echo "Suppression de AccFlags de la branche People"
ldapsearch  -x -b $PEOPLERDN,$BASEDN '(acctflags=*)' uid | grep uid:  | while read A
do

ID=`echo $A | cut -d : -f 2`

if [ "$ID" != "" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1  <<EOF
dn: uid=$ID,$PEOPLERDN,$BASEDN
changetype: modify
delete: acctflags
EOF

fi

done
# On ajoute admin dans tous les droits
# On ajoute admin dans tous les droits
# Il faut obligatoirement un member dans les groupofnames
echo "Traitement de la branche Rights...."
echo "Ajout de l'admin dans tous les groupes rights..."
ldapsearch  -x -b $RIGHTSRDN,$BASEDN '(objectclass=groupofNames)'  cn | grep -v \# | grep  dn: | cut -d : -f 2 | cut -d , -f1 | while read A
do

if [ "$A" != "" ]
then

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF
dn: $A,$RIGHTSRDN,$BASEDN
changetype: modify
add: member
member: uid=admin,$PEOPLERDN,$BASEDN
EOF

fi

done


# Recherche les groupofNames sans member dans Parcs
echo "Recherche et suppression des groupes Parcs vides..."
ldapsearch  -x -b $PARCSRDN,$BASEDN '(objectclass=groupofNames)' cn | grep cn: |cut -d : -f 2 | while read A
do
if [ "$A" != "" ]
then
	MEMBER_VIDE=`ldapsearch  -x -b $PARCSRDN,$BASEDN "(&(member=*)(cn=$A))" | grep member: >/dev/null 2>&1 && echo 1`
	if [ "$MEMBER_VIDE" != "1" ]
	then
		echo "Parcs $A est vide. Il va etre supprimé"
		ldapdelete -x -v -D "$ROOTDN" -w "$PASSLDAP" "cn=$A,$PARCSRDN,$BASEDN"
	fi	
fi

done


# Recherche les groupofNames sans member dans Groups
echo "Recherche et suppression des groupes Groups vides..."
ldapsearch  -x -b $GROUPSRDN,$BASEDN '(objectclass=groupofNames)' cn | grep cn: |cut -d : -f 2 | while read A
do
if [ "$A" != "" ]
then
	MEMBER_VIDE=`ldapsearch  -x -b $GROUPSRDN,$BASEDN "(&(member=*)(cn=$A))" | grep member: >/dev/null 2>&1 && echo 1`
	if [ "$MEMBER_VIDE" != "1" ]
	then
		echo "Groupe $A est vide. Il va être supprime"
		ldapdelete -x -v -D "$ROOTDN" -w "$PASSLDAP" "cn=$A,$GROUPSRDN,$BASEDN"
	fi	
fi

done

# Recherche les objectClass ipHost qui n'ont pas l'attribut ipHostNumber
echo "Recherche et modification des computers sans ipHostNumber..."
ldapsearch  -x -b $COMPUTERSRDN,$BASEDN '(objectclass=ipHost)' cn | grep cn: |cut -d : -f 2 | while read A
do
if [ "$A" != "" ]
then
	IPHOST_VIDE=`ldapsearch  -x -b $COMPUTERSRDN,$BASEDN "(&(ipHostNumber=*)(cn=$A))" | grep ipHostNumber: >/dev/null 2>&1 && echo 1`


if [ "$IPHOST_VIDE" != "1" ]
then
echo "$A ne possede pas l'attribut ipHostNumber. Il sera ajoute"

ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1 <<EOF
dn: cn=$A,$COMPUTERSRDN,$BASEDN
changetype: modify
add: ipHostNumber
ipHostNumber: 0.0.0.0
EOF
	fi	
fi

done

# Recherche des Groupes Mapping sans SambaSid 
echo "Recherche et modification des groupes mapping sans sambaSid..."
ldapsearch  -x -b $GROUPSRDN,$BASEDN '(objectclass=sambaGroupMapping)' cn | grep cn: |cut -d : -f 2 | while read A
do
if [ "$A" != "" ]
then
	SAMBASID_VIDE=`ldapsearch  -x -b $GROUPSRDN,$BASEDN "(&(sambaSID=*)(cn=$A))" | grep sambaSID: >/dev/null 2>&1 && echo 1`


	if [ "$SAMBASID_VIDE" != "1" ]
	then
		echo "$A ne possede pas l'attribut sambaSID. "
		
ldapmodify -x -v -D "$ROOTDN" -w "$PASSLDAP" > /dev/null 2>&1  <<EOF
dn: cn=$A,$GROUPSRDN,$BASEDN
changetype: modify
delete: objectClass
objectClass: sambaGroupMapping
EOF
	#
	net groupmap add ntgroup=$A unixgroup=$A	
	fi	
fi

done

echo "Sauvegarde de l'annuaire...."
slapcat > $REP_EXPORT/export_ldap_2.ldif

sleep 5

echo "Modification de slapd.conf"
/usr/share/se3/scripts/mkSlapdConf.sh > /dev/null

## effet de mkSlapdConf.sh => redemarrage de slapd et samba
echo "Arret de samba"
/etc/init.d/samba stop > /dev/null
echo "Arret de LDAP"
/etc/init.d/slapd stop > /dev/null

# On teste si on a des entrees digloo dedans
DIGLOO=`grep digloo-sarge $REP_EXPORT/export_ldap_2.ldif >/dev/null  && echo 1`


# Si les entrees digloo existe il faut les virer par un reimport
if [ "$DIGLOO" = "1" ]
then

	echo "Presence de l'entree digloo. On la supprime..."
	# Controle
	cp -r /var/lib/ldap/ $REP_EXPORT/ 
	rm -f /var/lib/ldap/*
	cp $REP_EXPORT/ldap/DB_CONFIG /var/lib/ldap/
	echo "Réimport de l'annuaire"
	slapadd -c -l $REP_EXPORT/export_ldap_2.ldif
fi	


#### Pour tester la compatibilite, il FAUT REIMPORTER l'annuaire... un restart fonctionne forcement...
#### echo "Adaptation de votre annuaire en vue de la migration vers etch."
# cp /etc/ldap/slapd.conf /etc/ldap/slapd.conf.$DATE.old
## modif wawa
echo "Bascule de ldap en schemacheck on et test de reimport ..."
perl -pi -e "s/^schemacheck.*/schemacheck  on/" /etc/ldap/slapd.conf
## /modif wawa

echo "Export de l'annuaire suite aux modifications...."
# slapcat > $REP_EXPORT/export_ldap_apres_adaptation_etch_$DATE.ldif
slapcat > $REP_EXPORT/annu_apres.new

cp /var/lib/ldap/DB_CONFIG /root/
rm -Rf /var/lib/ldap
mkdir /var/lib/ldap
cp /root/DB_CONFIG /var/lib/ldap

echo "Import de l'annuaire modifié."
mkdir -p /var/log/se3/migration
# slapadd -c -l $REP_EXPORT/export_ldap_apres_adaptation_etch_$DATE.ldif 2> /var/log/se3/migration/import_schemacheck_on.log

slapadd -c -l $REP_EXPORT/annu_apres.new 2> /var/log/se3/migration/import_schemacheck_on.log
if [ $(wc -c /var/log/se3/migration/import_schemacheck_on.log | cut -f1 -d" ") == 0 ]; then
  echo -e "$COLCMD"
  echo "L'adaptation de votre annuaire en vue de la migration vers etch s'est bien passe."
  echo -e "$COLTXT"
  ERREUR="no"
else
  echo -e "$COLTITRE"
  echo
  echo "ATTENTION !"
  echo
  echo "Un probleme est survenu lors de l'adaptation de votre annuaire en vue de la migration vers etch... Contacter la liste SAMBAEDU3 pour signaler cet incident."
  echo "Des entrees de l'annuaire n'ont pas ete integrees."
  echo "Un rapport d'erreur se trouve dans /var/log/se3/migration/import_schemacheck_on.log."
  echo "Une sauvegarde de l'annuaire precedent se trouve dans $REP_EXPORT/export_ldap.ldif."
  echo -e "$COLTXT"
  echo "Appuyer sur une touche pour continuer."
  read pause

MAIL_REPORT
fi


if [ "$OPT" != "sarge2etch" ]; then
	## modif wawa
	## on ne bascule pas schemacheck
	LCS=`ldapsearch  -x -b $RIGHTSRDN,$BASEDN "(cn=lcs_is_admin)" | grep cn: >/dev/null 2>&1 && echo 1`
	if [ "$LCS" = "1" ]
	then
		echo "Presence d'un serveur LCS. "
		echo "Repositionne la valeur schemacheck a off"
		perl -pi -e "s/^schemacheck.*/schemacheck  off/" /etc/ldap/slapd.conf
	fi
fi
echo "Demarrage de l'annuaire LDAP"
/etc/init.d/slapd start > /dev/null
echo "Demarrage de samba"
/etc/init.d/samba start > /dev/null

exit 0
