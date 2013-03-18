#!/bin/bash
#
## $Id$ ##
#
##### Script permettant de changer de la base dn de l'annuaire ldap #####
#  franck molle
# maj 09/2005

if [ "$1" = "--help" -o "$1" = "-h" ]
then
	echo "Script permettant de changer la base dn de l'annuaire LDAP"
	echo "Usage : Aucune option"
	exit
fi


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


### on suppose que l'on est sous debian ;) ####
WWWPATH="/var/www"

clear
echo -e "$COLTITRE"
echo "********************************"
echo "* SCRIPT PERMETTANT DE CHANGER *"
echo "* LA BASE DN DANS UN FICHIER LDIF  *"
echo "********************************"
echo -e "$COLTXT"
echo "Appuyez sur Entree pour continuer........"
echo -e "$COLTXT"
read pause

## recuperation des variables necessaires pour interoger mysql ###
if [ -e $WWWPATH/se3/includes/config.inc.php ]; then
	dbhost=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbhost=" | cut -d = -f 2 |cut -d \" -f 2`
	dbname=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbname=" | cut	-d = -f 2 |cut -d \" -f 2`
	dbuser=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbuser=" | cut	-d = -f 2 |cut -d \" -f 2`
	dbpass=`cat $WWWPATH/se3/includes/config.inc.php | grep "dbpass=" | cut	-d = -f 2 |cut -d \" -f 2`
	else
	echo "Fichier de conf inaccessible désolé !!"
	echo "le script ne peut se poursuivre"
	exit 1
	fi


	REPONSE=""
	while [ "$REPONSE" != "g" -a "$REPONSE" != "f" ]
	do
		echo -e "$COLTXT"
		echo -e "Voulez vous générer un fichier ldif depuis l'annuaire courant\n ou bien utiliser un fichier préalablement sauvegardé ? (${COLCHOIX}g/f${COLTXT}) $COLSAISIE\c"
		read REPONSE

	done

	if [ "$REPONSE" == "g" ]; then
		PASSLDAP=$(cat /etc/ldap.secret)
		ROOTDN=$(cat /etc/ldap/slapd.conf | grep rootdn | cut -f3 | sed -e s/\"//g)
		echo -e "$COLCMD"
		echo "patientez exportation de l'annaire en cours....."
		echo -e "$COLTXT"
				ldapsearch -xLLL -D "$ROOTDN" -w "$PASSLDAP" objectClass=* > /tmp/export_ldap.ldif && echo -e "$COLCMD Exportation effectueé avec succès"
		if [ $? != 0 ]; then
		ERREUR "L'exportation du fichier ldif a echoué !"
		fi
	else
		echo -e "$COLTXT"
		echo "Avant de continuer, vous devez avoir déposé votre fichier ldif nommé export_ldap.ldif  dans /tmp"
		echo -e "$COLTXT"
		POURSUIVRE
	fi




### Verification que le serveur ldap est bien sur se3 et non pas déporté"

#### Tout semble ok on peut poursuivre #
# echo "ce script va vous permettre de changer la base dn de l'annuaire sur votre fichier ldif "
echo -e "$COLTXT"
echo -e "Que desirez vous comme base dn de remplacement ?"
echo -e "Attention aux fautes de frappe !"
echo  -e "la base dn doit commencer par dc= ou bien ou= $COLSAISIE"
read NBASEDN

### recuperation des parametres actuels de l'annuaire dans la base ####
BASEDN=`echo "SELECT value FROM params WHERE name=\"ldap_base_dn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
ADMLDAP=`echo "SELECT value FROM params WHERE name=\"adminRdn\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`
PASSLDAP=`echo "SELECT value FROM params WHERE name=\"adminPw\"" | mysql -h $dbhost $dbname -u $dbuser -p$dbpass -N`

#echo "voici le Rdm de l annuaire $ADMLDAP"
#echo "et voici son pass $PASSLDAP"


echo -e "$COLINFO"
echo -e "Voici votre base dn actuelle $COLINFO"
echo $BASEDN
echo -e "${COLTXT}Et voici celle qui va la remplacer$COLINFO"
echo $NBASEDN
echo -e "$COTXT"


POURSUIVRE


BASEDNT=${BASEDN:0:6}

echo '#!/usr/bin/perl -w
# script de remplacement de la base dn de l annaire
# recherche de la base dn dans le fichier ldif, remplacement
# par la nouvelle base dn et ecriture d un nouveau fichier ldif
# franck molle 04/2004 sur les suggestions de plouf
open(ENTREE, "/tmp/export_ldap.ldif") or die "impossible";
undef $/;
my $dbasedn = $ARGV[0];
my $nbasedn = $ARGV[1];
my $basedn = $ARGV[2];
$contenu = <ENTREE>;
open(SORTIE, ">/root/export_ldap_mod.ldif");
$contenu=~s/$basedn/##replace##/g;
$contenu=~s/$dbasedn.*\n/####replace####/g;
$contenu=~s/####replace####.*\n/##replace##\n/g;
$contenu=~s/##replace##/$nbasedn/g;
print SORTIE $contenu;
close(SORTIE);'> /root/change_dn.pl


chmod 700 /root/change_dn.pl
### appel  du prog perl pour changer la chaine de caractere correspondant a la base dn ####

/root/change_dn.pl "$BASEDNT" "$NBASEDN" "$BASEDN" && echo -e "$COLCMD Nouveau fichier ldif généré avec succès dans /root/export_ldap_mod.ldif"
#/root/changedn.pl "$BASEDNT" "$NBASEDN" "$BASEDN" && echo -e "$COLCMD Nouveau fichier ldif généré avec succès dans /root/export_ldap_mod.ldif"
if [ $? != 0 ]; then
ERREUR "Le traitement du fichier ldif a echoué"
fi



### debug ###
#echo " resultat du cgt de base apres le prg perl"
#cat /root/export_ldap_mod.ldif | more

exit 0





